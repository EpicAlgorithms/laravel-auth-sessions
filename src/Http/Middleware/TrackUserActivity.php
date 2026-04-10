<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Middleware;

use App\Services\Admin\ImpersonationService;
use Closure;
use EpicAlgorithms\AuthSessions\Constants\SessionKey;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Services\AuthSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function __construct(
        private readonly AuthSessionService $authSessionService,
        private readonly ImpersonationService $impersonationService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // CRITICAL: Skip ALL activity tracking if impersonating
        if ($this->impersonationService->isImpersonating()) {
            return $response;
        }

        if (Auth::check()) {
            $user = Auth::user();
            $throttleSeconds = config('auth-sessions.last_seen_throttle', 60);

            // User.last_seen_at update (throttled to avoid unnecessary writes)
            if ($user->last_seen_at === null || $user->last_seen_at->diffInSeconds(now()) >= $throttleSeconds) {
                $user->update(['last_seen_at' => now()]);
            }

            // AuthSession.last_seen_at update (throttled, reuse from request if available)
            $authSession = $request->attributes->get('authSession');

            if (! $authSession) {
                $authSessionId = session(SessionKey::AUTH_SESSION_ID);
                if ($authSessionId) {
                    $authSession = AuthSession::find($authSessionId);
                }
            }

            if ($authSession && $authSession->last_seen_at->diffInSeconds(now()) >= $throttleSeconds) {
                $this->authSessionService->updateLastSeen($authSession);
            }
        }

        return $response;
    }
}
