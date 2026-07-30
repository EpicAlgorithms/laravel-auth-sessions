<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Middleware;

use Closure;
use DateTimeInterface;
use EpicAlgorithms\AuthSessions\Constants\SessionKey;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Services\AuthSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function __construct(
        private readonly AuthSessionService $authSessionService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // CRITICAL: Skip ALL activity tracking if impersonating
        $impersonationCheck = config('auth-sessions.impersonation_check');
        if (is_callable($impersonationCheck) && $impersonationCheck()) {
            return $response;
        }

        $user = Auth::user();

        if ($user !== null) {
            $throttleSeconds = config('auth-sessions.last_seen_throttle', 60);

            // User.last_seen_at update (throttled to avoid unnecessary writes).
            // last_seen_at belongs to the host application's user model, so it
            // is read as a dynamic attribute; anything that is not a date is
            // treated as "never seen".
            $lastSeen = $user->getAttribute('last_seen_at');
            $lastSeen = $lastSeen instanceof DateTimeInterface ? Carbon::instance($lastSeen) : null;

            if ($lastSeen === null || $lastSeen->diffInSeconds(now()) >= $throttleSeconds) {
                $user->update(['last_seen_at' => now()]);
            }

            // AuthSession.last_seen_at update (throttled, reuse from request if available)
            $authSession = $request->attributes->get('authSession');

            if (! $authSession instanceof AuthSession) {
                $authSession = AuthSession::findById(session(SessionKey::AUTH_SESSION_ID));
            }

            if ($authSession !== null && $authSession->last_seen_at->diffInSeconds(now()) >= $throttleSeconds) {
                $this->authSessionService->updateLastSeen($authSession);
            }
        }

        return $response;
    }
}
