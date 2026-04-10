<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Services;

use EpicAlgorithms\AuthSessions\Enums\LoginMethod;
use EpicAlgorithms\AuthSessions\Enums\SessionRevokeReason;
use EpicAlgorithms\AuthSessions\Models\AuthDevice;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AuthSessionService
{
    public function __construct(
        private readonly DeviceDetectionService $deviceDetectionService,
    ) {}

    public function createSession(
        Authenticatable $user,
        LoginMethod $loginMethod,
        string $ip,
        string $userAgent,
        bool $isRemembered,
        ?string $laravelSessionId = null,
        ?string $deviceId = null,
    ): AuthSession {
        $deviceInfo = $this->deviceDetectionService->detect($userAgent);
        $lifetime = $isRemembered
            ? config('auth-sessions.remember_me_duration')
            : config('auth-sessions.session_lifetime');

        return DB::transaction(function () use ($user, $loginMethod, $ip, $userAgent, $isRemembered, $laravelSessionId, $deviceId, $deviceInfo, $lifetime) {
            return AuthSession::create([
                'user_id' => $user->getAuthIdentifier(),
                'device_id' => $deviceId,
                'laravel_session_id' => $laravelSessionId,
                'login_method_id' => $loginMethod,
                'device_type_id' => $deviceInfo['device_type_id'],
                'os_name' => $deviceInfo['platform'],
                'os_version' => $deviceInfo['platform_version'],
                'browser_name' => $deviceInfo['browser'],
                'browser_version' => $deviceInfo['browser_version'],
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'is_remembered' => $isRemembered,
                'last_seen_at' => now(),
                'expires_at' => now()->addMinutes($lifetime),
                'created_at' => now(),
            ]);
        });
    }

    public function revokeSession(
        AuthSession $session,
        SessionRevokeReason $reason,
        bool $invalidateRuntimeSession = true,
    ): bool {
        return DB::transaction(function () use ($session, $reason, $invalidateRuntimeSession) {
            // 1. Revoke the auth_session
            $revoked = $session->revoke($reason);

            // 2. Set requires_reauth for this device
            if ($session->device_id) {
                AuthDevice::updateOrCreate(
                    ['user_id' => $session->user_id, 'device_id' => $session->device_id],
                    ['requires_reauth_at' => now()]
                );
            }

            // 3. Delete from Laravel sessions table if requested
            if ($invalidateRuntimeSession && $session->laravel_session_id) {
                AuthSession::deleteLaravelSession($session->laravel_session_id);
            }

            return $revoked;
        });
    }

    public function revokeOtherSessions(
        Authenticatable $user,
        AuthSession $currentSession,
    ): int {
        return DB::transaction(function () use ($user, $currentSession) {
            // 1. Get all other sessions
            $otherSessions = AuthSession::where('user_id', $user->getAuthIdentifier())
                ->where('id', '!=', $currentSession->id)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->get();

            // 2. Revoke auth_sessions
            $count = AuthSession::revokeAllExcept(
                $user,
                $currentSession->id,
                SessionRevokeReason::LogoutOtherDevices
            );

            // 3. Set requires_reauth for other devices
            if ($currentSession->device_id) {
                AuthDevice::where('user_id', $user->getAuthIdentifier())
                    ->where('device_id', '!=', $currentSession->device_id)
                    ->update(['requires_reauth_at' => now()]);
            }

            // 4. Delete runtime sessions from Laravel sessions table
            foreach ($otherSessions as $session) {
                if ($session->laravel_session_id) {
                    AuthSession::deleteLaravelSession($session->laravel_session_id);
                }
            }

            return $count;
        });
    }

    public function getActiveSessions(Authenticatable $user): Collection
    {
        return AuthSession::where('user_id', $user->getAuthIdentifier())
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderBy('last_seen_at', 'desc')
            ->get();
    }

    public function revokeSessionById(Authenticatable $user, string $authSessionId, SessionRevokeReason $reason): bool
    {
        $session = AuthSession::find($authSessionId);

        if (! $session || $session->user_id !== $user->getAuthIdentifier()) {
            return false;
        }

        return $this->revokeSession($session, $reason);
    }

    public function revokeOtherSessionsByLaravelSessionId(Authenticatable $user, string $laravelSessionId): int
    {
        $currentSession = AuthSession::byLaravelSessionId($laravelSessionId)->first();

        if (! $currentSession || $currentSession->user_id !== $user->getAuthIdentifier()) {
            return 0;
        }

        return $this->revokeOtherSessions($user, $currentSession);
    }

    public function revokeOtherSessionsById(Authenticatable $user, string $authSessionId): int
    {
        $currentSession = AuthSession::find($authSessionId);

        if (! $currentSession || $currentSession->user_id !== $user->getAuthIdentifier()) {
            return 0;
        }

        return $this->revokeOtherSessions($user, $currentSession);
    }

    public function updateLastSeen(AuthSession $session): bool
    {
        return $session->touchLastSeen();
    }

    public function syncLaravelSessionId(
        AuthSession $authSession,
        string $newSessionId,
    ): bool {
        return $authSession->update(['laravel_session_id' => $newSessionId]);
    }

    public function checkInactivityExpiry(AuthSession $session): bool
    {
        if ($session->is_remembered) {
            return false; // Remember me sessions don't have inactivity expiry
        }

        $inactivityMinutes = config('auth-sessions.inactivity_timeout', 120);

        return $session->last_seen_at->diffInMinutes(now()) > $inactivityMinutes;
    }
}
