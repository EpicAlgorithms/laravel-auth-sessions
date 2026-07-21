<?php

declare(strict_types=1);

return [
    'session_lifetime' => (int) env('AUTH_SESSIONS_SESSION_LIFETIME', 120),
    'inactivity_timeout' => (int) env('AUTH_SESSIONS_INACTIVITY_TIMEOUT', 120),
    'remember_me_duration' => (int) env('AUTH_SESSIONS_REMEMBER_ME_DURATION', 43200),
    'last_seen_throttle' => (int) env('AUTH_SESSIONS_LAST_SEEN_THROTTLE', 60),
    'user_model' => \App\Models\User::class,
    'route_prefix' => 'settings',
    'middleware' => ['web', 'auth'],
    // When true (default), the package auto-registers its routes via the service provider.
    // Set to false if your application defines its own routes for /settings/sessions
    // that point to a subclass of BaseSessionsController.
    'register_routes' => true,
    'controller' => \App\Http\Controllers\Account\SessionsController::class,
    // Optional closure that returns true when the current request is an impersonation session.
    // Rd-hub overrides this to call its ImpersonationService. Leave null to disable.
    'impersonation_check' => null,
    'views' => [
        'sessions' => 'auth-sessions::sessions.index',
    ],
    'layout' => 'layouts.app',

    // Opt-in JSON REST API, built on epicalgorithms/laravel-api-kit. Additive to
    // the web session-manager routes above and gated entirely by `http.mode`:
    //   headless (default) - no API routes are registered.
    //   api                - the JSON endpoints under `prefix` are registered.
    //   ui                 - superset of api (reserved; behaves like api here).
    // See EpicAlgorithms\ApiKit\Http\HttpMode.
    'http' => [
        'mode' => env('AUTH_SESSIONS_HTTP_MODE', 'headless'),
        'prefix' => 'api/account',
        'middleware' => ['api'],
        'auth_middleware' => ['auth'],
        'rate_limit' => env('AUTH_SESSIONS_API_RATE_LIMIT', '60,1'),
    ],
];
