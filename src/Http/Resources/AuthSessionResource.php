<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Resources;

use EpicAlgorithms\AuthSessions\Models\AuthSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON representation of an {@see AuthSession} for the account API.
 *
 * @mixin AuthSession
 */
class AuthSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'login_method_id' => $this->login_method_id,
            'device_type_id' => $this->device_type_id,
            'os_name' => $this->os_name,
            'os_version' => $this->os_version,
            'browser_name' => $this->browser_name,
            'browser_version' => $this->browser_version,
            'ip_address' => $this->ip_address,
            'is_remembered' => (bool) $this->is_remembered,
            'is_active' => $this->isActive(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
