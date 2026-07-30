<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Concerns;

use Illuminate\Http\Request;

/**
 * Reads the device_id cookie.
 *
 * Request::cookie() is typed array|string|null because a cookie name can carry
 * a bracketed array payload. A device id is always a single opaque string, so
 * anything else is treated as absent.
 */
trait ReadsDeviceCookie
{
    protected function cookieDeviceId(Request $request): ?string
    {
        $deviceId = $request->cookie('device_id');

        return is_string($deviceId) && $deviceId !== '' ? $deviceId : null;
    }
}
