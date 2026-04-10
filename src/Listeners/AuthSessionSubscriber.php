<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Listeners;

use EpicAlgorithms\AuthSessions\Constants\SessionKey;
use EpicAlgorithms\AuthSessions\Enums\SessionRevokeReason;
use EpicAlgorithms\AuthSessions\Events\NewDeviceLogin;
use EpicAlgorithms\AuthSessions\Models\AuthDevice;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Services\AuthSessionService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

class AuthSessionSubscriber
{
    public function __construct(
        private readonly AuthSessionService $authSessionService,
    ) {}

    /**
     * Handle user login events.
     *
     * Creates an auth_session record when a user logs in.
     * Skips if impersonating to avoid polluting target user's session history.
     */
    public function handleLogin(Login $event): void
    {
        // Skip if impersonating - we don't want to track impersonation as a real user session
        $impersonationCheck = config('auth-sessions.impersonation_check');
        if (is_callable($impersonationCheck) && $impersonationCheck()) {
            return;
        }

        // Get login method from session hint (set by controllers before Auth::login())
        $loginMethod = session()->pull(SessionKey::LOGIN_METHOD);

        if (! $loginMethod) {
            return; // No login method hint, skip
        }

        // Create auth session
        $authSession = $this->authSessionService->createSession(
            user: $event->user,
            loginMethod: $loginMethod,
            ip: request()->ip() ?? '0.0.0.0',
            userAgent: request()->userAgent() ?? '',
            isRemembered: $event->remember,
            laravelSessionId: session()->getId(),
            deviceId: request()->cookie('device_id'),
        );

        // Store auth_session_id in Laravel session for later reference
        session()->put(SessionKey::AUTH_SESSION_ID, $authSession->id);

        // Register device and clear requires_reauth flag (manual login = reauthorization)
        if ($authSession->device_id) {
            $device = AuthDevice::firstOrCreate(
                ['user_id' => $event->user->getAuthIdentifier(), 'device_id' => $authSession->device_id],
                ['requires_reauth_at' => null]
            );

            if ($device->requires_reauth_at) {
                AuthDevice::where('user_id', $event->user->getAuthIdentifier())
                    ->where('device_id', $authSession->device_id)
                    ->update(['requires_reauth_at' => null]);
            }

            if ($device->wasRecentlyCreated) {
                // Don't send "new device" email if this is the initial registration login
                $skipNewDeviceEmail = session()->pull(SessionKey::SKIP_NEW_DEVICE_EMAIL, false);

                if (! $skipNewDeviceEmail) {
                    event(new NewDeviceLogin(
                        user: $event->user,
                        session: $authSession,
                        ip: request()->ip() ?? '0.0.0.0',
                        userAgent: request()->userAgent() ?? '',
                    ));
                }
            }
        }
    }

    /**
     * Handle user logout events.
     *
     * Revokes the auth_session and associated remember tokens.
     * Also deletes the Laravel runtime session from the sessions table.
     */
    public function handleLogout(Logout $event): void
    {
        $authSessionId = session(SessionKey::AUTH_SESSION_ID);

        if (! $authSessionId) {
            return;
        }

        $session = AuthSession::find($authSessionId);

        if (! $session) {
            return;
        }

        // revokeSession also:
        // 1. Revokes the auth_session record
        // 2. Revokes associated remember_tokens
        // 3. Deletes Laravel runtime session from sessions table
        $this->authSessionService->revokeSession(
            session: $session,
            reason: SessionRevokeReason::find(SessionRevokeReason::USER_LOGOUT),
            invalidateRuntimeSession: true,
        );
    }

    /**
     * Register the listeners for the subscriber.
     *
     * NOTE: Authenticated event is NOT subscribed here to avoid write amplification.
     * last_seen_at updates are handled by TrackUserActivity middleware with throttling.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            // Authenticated event intentionally NOT subscribed - see TrackUserActivity middleware
        ];
    }
}
