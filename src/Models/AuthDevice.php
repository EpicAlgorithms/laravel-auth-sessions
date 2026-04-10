<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthDevice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'requires_reauth_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth-sessions.user_model'));
    }

    public function requiresReauth(): bool
    {
        return $this->requires_reauth_at !== null;
    }
}
