<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

$controller = config('auth-sessions.controller');

Route::prefix(config('auth-sessions.route_prefix'))
    ->middleware(config('auth-sessions.middleware'))
    ->group(function () use ($controller) {
        Route::get('/sessions', [$controller, 'index'])->name('auth-sessions.index');
        Route::delete('/sessions', [$controller, 'destroyOthers'])->name('auth-sessions.destroy-others');
        Route::delete('/sessions/{authSession}', [$controller, 'destroy'])->name('auth-sessions.destroy');
    });
