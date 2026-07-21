<?php

declare(strict_types=1);

use EpicAlgorithms\AuthSessions\Http\Controllers\Api\DeviceApiController;
use EpicAlgorithms\AuthSessions\Http\Controllers\Api\SessionApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Sessions JSON API
|--------------------------------------------------------------------------
|
| Loaded by AuthSessionsServiceProvider::boot() via api-kit's
| registerModuleApi('auth-sessions', ...) only when http.mode is api/ui. The
| enclosing group already applies the prefix (api/account), base middleware,
| name prefix (auth-sessions.api.) and throttle. Every endpoint here is
| account-owner scoped and requires authentication, so the whole file sits
| behind the module's auth middleware.
|
*/

Route::middleware(config('auth-sessions.http.auth_middleware', ['auth']))->group(function () {
    Route::get('sessions', [SessionApiController::class, 'index'])->name('sessions.index');
    Route::delete('sessions', [SessionApiController::class, 'destroyOthers'])->name('sessions.destroy-others');
    Route::get('sessions/{authSession}', [SessionApiController::class, 'show'])->name('sessions.show');
    Route::delete('sessions/{authSession}', [SessionApiController::class, 'destroy'])->name('sessions.destroy');

    Route::get('devices', [DeviceApiController::class, 'index'])->name('devices.index');
    Route::delete('devices/{authDevice}', [DeviceApiController::class, 'destroy'])->name('devices.destroy');
});
