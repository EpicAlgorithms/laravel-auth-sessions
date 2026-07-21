# Laravel Auth Sessions

Stateful session and device tracking for Laravel. Models, middleware, publishable views.

## Installation

    composer require epicalgorithms/laravel-auth-sessions

## Quick start

    php artisan vendor:publish --tag=auth-sessions-config
    php artisan vendor:publish --tag=auth-sessions-migrations
    php artisan migrate

Add `HasAuthSessions` trait to your `User` model. Register the middleware in `bootstrap/app.php`. Call `Route::authSessions()` in your routes file.

See `config/auth-sessions.php` for configuration options.

## Pruning expired sessions

Revoked/expired rows are kept for a 30-day grace window, then removed by the
`auth-sessions:prune` command. Schedule it (e.g. daily) in your app:

    // routes/console.php (Laravel 11+)
    use Illuminate\Support\Facades\Schedule;

    Schedule::command('auth-sessions:prune')->daily();

## Assumptions

- **Runtime session invalidation** (`AuthSession::deleteLaravelSession()`, used
  when a session is revoked) only runs when `session.driver` is `database`.
  Under other drivers it is a no-op; the auth-session row is still revoked.
- **Device re-auth** (`EnforceDeviceReauth`) and the default schema assume an
  integer `user_id`. String/UUID/ULID user keys do not error, but you must
  widen the `user_id` columns in a published migration to match your users
  table.

## Publishing views and notifications

    php artisan vendor:publish --tag=auth-sessions-views
    php artisan vendor:publish --tag=auth-sessions-notifications
