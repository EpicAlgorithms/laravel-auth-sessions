<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Middleware;

use Closure;
use EpicAlgorithms\AuthSessions\Constants\SessionKey;
use EpicAlgorithms\AuthSessions\Enums\SessionRevokeReason;
use EpicAlgorithms\AuthSessions\Http\Middleware\Concerns\ForcesLogout;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Services\AuthSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionExpiry
{
    use ForcesLogout;

    public function __construct(
        private readonly AuthSessionService $authSessionService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $authSessionId = session(SessionKey::AUTH_SESSION_ID);

            if ($authSessionId) {
                $authSession = AuthSession::find($authSessionId);

                // Store on request for other middleware to reuse
                $request->attributes->set('authSession', $authSession);

                if (! $authSession || ! $authSession->isActive()) {
                    return $this->forceLogout($request, 'Session expired.');
                }

                // Inactivity check (non-remembered sessions only)
                if ($this->authSessionService->checkInactivityExpiry($authSession)) {
                    $this->authSessionService->revokeSession(
                        $authSession,
                        SessionRevokeReason::InactivityExpiry
                    );

                    return $this->forceLogout($request, 'Session expired due to inactivity.');
                }
            }
        }

        return $next($request);
    }
}
