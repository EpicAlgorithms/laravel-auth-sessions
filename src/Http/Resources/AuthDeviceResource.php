<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Resources;

use EpicAlgorithms\AuthSessions\Models\AuthDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON representation of an {@see AuthDevice} for the account API.
 *
 * @mixin AuthDevice
 */
class AuthDeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'requires_reauth' => $this->requiresReauth(),
            'requires_reauth_at' => $this->requires_reauth_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
