<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Models;

use EpicAlgorithms\AuthSessions\Concerns\ResolvesUserModel;
use EpicAlgorithms\AuthSessions\Enums\DeviceType;
use EpicAlgorithms\AuthSessions\Enums\LoginMethod;
use EpicAlgorithms\AuthSessions\Enums\SessionRevokeReason;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $device_id
 * @property string|null $laravel_session_id
 * @property int $login_method_id
 * @property int|null $device_type_id
 * @property string|null $os_name
 * @property string|null $os_version
 * @property string|null $browser_name
 * @property string|null $browser_version
 * @property string $ip_address
 * @property string $user_agent
 * @property bool $is_remembered
 * @property Carbon $last_seen_at
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property int|null $revoke_reason_id
 * @property Carbon $created_at
 * @property-read Model|null $user
 * @property-read LoginMethod|null $loginMethod
 * @property-read DeviceType|null $deviceType
 * @property-read SessionRevokeReason|null $revokeReason
 */
class AuthSession extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    use HasUlids;
    use ResolvesUserModel;

    protected $guarded = ['id'];

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
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

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo($this->userModel());
    }

    /**
     * @return BelongsTo<LoginMethod, $this>
     */
    public function loginMethod(): BelongsTo
    {
        return $this->belongsTo(LoginMethod::class, 'login_method_id');
    }

    /**
     * @return BelongsTo<DeviceType, $this>
     */
    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class, 'device_type_id');
    }

    /**
     * @return BelongsTo<SessionRevokeReason, $this>
     */
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

    /**
     * @param  Builder<AuthSession>  $query
     * @return Builder<AuthSession>
     */
    public function scopeByLaravelSessionId(Builder $query, string $sessionId): Builder
    {
        return $query->where('laravel_session_id', $sessionId);
    }

    /**
     * Find a session by an id that came out of the Laravel session store.
     *
     * Session values are mixed, and Model::find() widens to a Collection when
     * handed an array, so the key is narrowed to a scalar id first.
     */
    public static function findById(mixed $id): ?static
    {
        if (! is_string($id) && ! is_int($id)) {
            return null;
        }

        return static::query()->find((string) $id);
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

    /**
     * Delete the underlying Laravel runtime session row.
     *
     * ASSUMPTION: this only makes sense when the application uses the
     * `database` session driver, where runtime sessions live in the `sessions`
     * table. Under any other driver (file, cookie, redis, array, ...) there is
     * no such table, so we skip the delete rather than error. Consumers that
     * need cross-driver invalidation should handle it at the driver level.
     */
    public static function deleteLaravelSession(string $sessionId): bool
    {
        if (config('session.driver') !== 'database') {
            return false;
        }

        return DB::table('sessions')->where('id', $sessionId)->delete() > 0;
    }
}
