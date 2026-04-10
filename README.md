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

## Publishing views and notifications

    php artisan vendor:publish --tag=auth-sessions-views
    php artisan vendor:publish --tag=auth-sessions-notifications
