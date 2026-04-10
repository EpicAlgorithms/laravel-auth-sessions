<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Middleware;

use Closure;
use EpicAlgorithms\AuthSessions\Constants\SessionKey;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Services\AuthSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SyncAuthSessionId
{
    public function __construct(
        private readonly AuthSessionService $authSessionService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * Detects Laravel session ID regeneration and syncs it to auth_sessions table.
     * This prevents laravel_session_id from becoming stale after session regeneration.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $originalSessionId = session()->getId();

        $response = $next($request);

        $newSessionId = session()->getId();

        // Detect session regeneration
        if (Auth::check() && $originalSessionId !== $newSessionId) {
            $authSession = $request->attributes->get('authSession');

            if (! $authSession) {
                $authSessionId = session(SessionKey::AUTH_SESSION_ID);
                if ($authSessionId) {
                    $authSession = AuthSession::find($authSessionId);
                }
            }

            if ($authSession) {
                $this->authSessionService->syncLaravelSessionId(
                    $authSession,
                    $newSessionId
                );
            }
        }

        return $response;
    }
}
