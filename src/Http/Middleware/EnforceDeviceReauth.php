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
            $deviceId = $request->cookie('device_id');

            if ($deviceId && $this->requiresReauth(Auth::id(), $deviceId)) {
                return $this->forceLogout($request, 'Please log in again to continue.');
            }
        }

        return $next($request);
    }

    private function requiresReauth(int $userId, string $deviceId): bool
    {
        return AuthDevice::where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->whereNotNull('requires_reauth_at')
            ->exists();
    }
}
