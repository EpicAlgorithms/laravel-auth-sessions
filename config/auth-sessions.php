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
    'controller' => \App\Http\Controllers\Account\SessionsController::class,
    // Optional closure that returns true when the current request is an impersonation session.
    // Rd-hub overrides this to call its ImpersonationService. Leave null to disable.
    'impersonation_check' => null,
    'views' => [
        'sessions' => 'auth-sessions::sessions.index',
    ],
    'layout' => 'layouts.app',
];
