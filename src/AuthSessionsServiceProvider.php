<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions;

use Illuminate\Support\ServiceProvider;
use EpicAlgorithms\AuthSessions\Services\AuthSessionService;
use EpicAlgorithms\AuthSessions\Services\DeviceDetectionService;

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
        $this->loadRoutesFrom(__DIR__.'/../routes/auth-sessions.php');

        if (class_exists(\EpicAlgorithms\AuthSessions\Listeners\AuthSessionSubscriber::class)) {
            $this->app['events']->subscribe(\EpicAlgorithms\AuthSessions\Listeners\AuthSessionSubscriber::class);
        }

        if ($this->app->runningInConsole()) {
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
}
