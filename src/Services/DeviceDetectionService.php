<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Services;

use EpicAlgorithms\AuthSessions\Enums\DeviceType;
use OzanKurt\Agent\Agent;
use Throwable;

class DeviceDetectionService
{
    /**
     * Parse a User-Agent string and return device info array for session creation.
     *
     * @return array{device_type_id: int, device: string|null, platform: string|null, platform_version: string|null, browser: string|null, browser_version: string|null}
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
            'platform_version' => $this->safeVersion($agent, $platform),
            'browser' => $browser,
            'browser_version' => $this->safeVersion($agent, $browser),
        ];
    }

    // ozankurt/agent 1.0.3 calls self::VER in version(), which was removed upstream in
    // mobiledetect/mobiledetectlib 4.x. Until the fork catches up, swallow the failure
    // so a broken version lookup does not block session creation.
    private function safeVersion(Agent $agent, ?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        try {
            return $this->resolveString($agent->version($name));
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveDeviceType(Agent $agent): int
    {
        if ($agent->isTablet()) {
            return DeviceType::TABLET;
        }

        if ($agent->isMobile()) {
            return DeviceType::MOBILE;
        }

        return DeviceType::DESKTOP;
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
