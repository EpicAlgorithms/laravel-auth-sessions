<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Services;

use EpicAlgorithms\AuthSessions\Enums\DeviceType;
use OzanKurt\Agent\Agent;

class DeviceDetectionService
{
    /**
     * Parse a User-Agent string and return device info array for session creation.
     *
     * @return array{device_type_id: DeviceType, device: string|null, platform: string|null, platform_version: string|null, browser: string|null, browser_version: string|null}
     */
    public function detect(string $userAgent): array
    {
        $agent = new Agent;
        $agent->setUserAgent($userAgent);

        $platform = $this->resolveString($agent->platform());
        $browser = $this->resolveString($agent->browser());

        return [
            'device_type_id' => $this->resolveDeviceType($agent),
            'device' => $this->resolveDevice($agent),
            'platform' => $platform,
            'platform_version' => $platform ? $this->resolveString($agent->version($platform)) : null,
            'browser' => $browser,
            'browser_version' => $browser ? $this->resolveString($agent->version($browser)) : null,
        ];
    }

    private function resolveDeviceType(Agent $agent): DeviceType
    {
        if ($agent->isTablet()) {
            return DeviceType::Tablet;
        }

        if ($agent->isMobile()) {
            return DeviceType::Mobile;
        }

        return DeviceType::Desktop;
    }

    private function resolveDevice(Agent $agent): ?string
    {
        if (! $agent->isMobile() && ! $agent->isTablet()) {
            return null;
        }

        return $this->resolveString($agent->device());
    }

    private function resolveString(string|bool|null $value): ?string
    {
        if (! $value || $value === 'unknown' || is_bool($value)) {
            return null;
        }

        return (string) $value;
    }
}
