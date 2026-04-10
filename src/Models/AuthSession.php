<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Models;

use EpicAlgorithms\AuthSessions\Enums\DeviceType;
use EpicAlgorithms\AuthSessions\Enums\LoginMethod;
use EpicAlgorithms\AuthSessions\Enums\SessionRevokeReason;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AuthSession extends Model
{
    use HasFactory;
    use HasUlids;

    protected $guarded = ['id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'login_method_id' => 'integer',
            'device_type_id' => 'integer',
            'is_remembered' => 'boolean',
            'last_seen_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'revoke_reason_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth-sessions.user_model'));
    }

    public function loginMethod(): BelongsTo
    {
        return $this->belongsTo(LoginMethod::class, 'login_method_id');
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class, 'device_type_id');
    }

    public function revokeReason(): BelongsTo
    {
        return $this->belongsTo(SessionRevokeReason::class, 'revoke_reason_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function scopeByLaravelSessionId(Builder $query, string $sessionId): Builder
    {
        return $query->where('laravel_session_id', $sessionId);
    }

    public function touchLastSeen(): bool
    {
        return $this->update(['last_seen_at' => now()]);
    }

    public function revoke(int $reasonId): bool
    {
        return $this->update([
            'revoked_at' => now(),
            'revoke_reason_id' => $reasonId,
        ]);
    }

    public static function revokeAllExcept(Authenticatable $user, string $exceptId, int $reasonId): int
    {
        return static::where('user_id', $user->getAuthIdentifier())
            ->where('id', '!=', $exceptId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoke_reason_id' => $reasonId,
            ]);
    }

    public static function revokeAllForUser(Authenticatable $user, int $reasonId): int
    {
        return static::where('user_id', $user->getAuthIdentifier())
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoke_reason_id' => $reasonId,
            ]);
    }

    public static function deleteExpired(): int
    {
        return static::where('expires_at', '<', now()->subDays(30))->delete();
    }

    public static function deleteLaravelSession(string $sessionId): bool
    {
        return DB::table('sessions')->where('id', $sessionId)->delete() > 0;
    }
}
