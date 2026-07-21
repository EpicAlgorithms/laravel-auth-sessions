<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions;

use EpicAlgorithms\AuthSessions\Console\Commands\PruneAuthSessionsCommand;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Services\AuthSessionService;
use EpicAlgorithms\AuthSessions\Services\DeviceDetectionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuthSessionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/auth-sessions.php', 'auth-sessions');

        $this->app->singleton(DeviceDetectionService::class);
        $this->app->singleton(AuthSessionService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'auth-sessions');
        \Illuminate\Support\Facades\Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'auth-sessions');

        $this->registerRouteBindings();

        if (config('auth-sessions.register_routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/auth-sessions.php');
        }

        if (class_exists(\EpicAlgorithms\AuthSessions\Listeners\AuthSessionSubscriber::class)) {
            $this->app['events']->subscribe(\EpicAlgorithms\AuthSessions\Listeners\AuthSessionSubscriber::class);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneAuthSessionsCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/auth-sessions.php' => config_path('auth-sessions.php'),
            ], 'auth-sessions-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'auth-sessions-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/auth-sessions'),
            ], 'auth-sessions-views');

            $this->publishes([
                __DIR__.'/../stubs/notifications/NewDeviceLoginNotification.stub' => app_path('Notifications/NewDeviceLoginNotification.php'),
                __DIR__.'/../stubs/listeners/SendNewDeviceLoginNotification.stub' => app_path('Listeners/SendNewDeviceLoginNotification.php'),
            ], 'auth-sessions-notifications');
        }
    }

    /**
     * Resolve the {authSession} route parameter through the authenticated
     * user so a session can only ever be resolved by its owner. An unknown id
     * or one belonging to another user yields a 404 (ModelNotFoundException)
     * instead of leaking existence or acting on someone else's row.
     */
    protected function registerRouteBindings(): void
    {
        Route::bind('authSession', function ($value) {
            $user = Auth::user();

            if ($user === null) {
                abort(404);
            }

            // Prefer the user's own relationship when the model uses the
            // HasAuthSessions trait; otherwise fall back to a query scoped by
            // the owner's key. Both paths 404 on a missing/foreign id.
            if (method_exists($user, 'authSessions')) {
                return $user->authSessions()->whereKey($value)->firstOrFail();
            }

            return AuthSession::query()
                ->whereKey($value)
                ->where('user_id', $user->getAuthIdentifier())
                ->firstOrFail();
        });
    }
}
