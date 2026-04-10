<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Middleware;

use Closure;
use EpicAlgorithms\AuthSessions\Constants\SessionKey;
use EpicAlgorithms\AuthSessions\Enums\LoginMethod;
use EpicAlgorithms\AuthSessions\Services\AuthSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthSessionExists
{
    public function __construct(
        private readonly AuthSessionService $authSessionService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $impersonationCheck = config('auth-sessions.impersonation_check');
        if (is_callable($impersonationCheck) && $impersonationCheck()) {
            return $next($request);
        }

        // Auth exists but no auth_session_id = remember auto-login
        if (Auth::check() && ! session()->has(SessionKey::AUTH_SESSION_ID)) {
            $user = Auth::user();

            if ($user !== null) {
                $authSession = $this->authSessionService->createSession(
                    user: $user,
                    loginMethod: LoginMethod::RememberToken,
                    ip: $request->ip() ?? '0.0.0.0',
                    userAgent: $request->userAgent() ?? '',
                    isRemembered: true,
                    laravelSessionId: session()->getId(),
                    deviceId: $request->cookie('device_id'),
                );

                session()->put(SessionKey::AUTH_SESSION_ID, $authSession->id);
            }
        }

        return $next($request);
    }
}
