<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Middleware;

use Closure;
use EpicAlgorithms\AuthSessions\Http\Middleware\Concerns\ForcesLogout;
use EpicAlgorithms\AuthSessions\Models\AuthDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceDeviceReauth
{
    use ForcesLogout;

    public function handle(Request $request, Closure $next): Response
    {
        // Only check during remember cookie auto-login
        if (Auth::check() && Auth::viaRemember()) {
            // Cookie values are array|string|null; only a single scalar cookie
            // identifies a device.
            $deviceId = $request->cookie('device_id');
            $userId = Auth::id();

            if (is_string($deviceId) && $deviceId !== '' && (is_int($userId) || is_string($userId))
                && $this->requiresReauth($userId, $deviceId)) {
                return $this->forceLogout($request, 'Please log in again to continue.');
            }
        }

        return $next($request);
    }

    /**
     * ASSUMPTION: the authenticatable's primary key is comparable to the
     * `auth_devices.user_id` column. The default schema types that column as a
     * `foreignId` (integer), so integer keys work as-is. The signature accepts
     * `int|string` so string/UUID/ULID user keys do not raise a TypeError;
     * consumers using non-integer user keys must widen the `user_id` column in
     * a published migration to match.
     */
    private function requiresReauth(int|string $userId, string $deviceId): bool
    {
        return AuthDevice::where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->whereNotNull('requires_reauth_at')
            ->exists();
    }
}
