<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Concerns;

use EpicAlgorithms\AuthSessions\Constants\SessionKey;
use EpicAlgorithms\AuthSessions\Models\AuthDevice;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasAuthSessions
{
    public function authSessions(): HasMany
    {
        return $this->hasMany(AuthSession::class, 'user_id');
    }

    public function authDevices(): HasMany
    {
        return $this->hasMany(AuthDevice::class, 'user_id');
    }

    public function currentSession(): ?AuthSession
    {
        $sessionId = session(SessionKey::AUTH_SESSION_ID);

        if (! $sessionId) {
            return null;
        }

        return $this->authSessions()->whereKey($sessionId)->first();
    }

    public function scopeWithActiveSessions(Builder $query): Builder
    {
        return $query->whereHas('authSessions', function (Builder $q) {
            $q->whereNull('revoked_at')->where('expires_at', '>', now());
        });
    }
}
