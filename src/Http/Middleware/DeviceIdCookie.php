<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DeviceIdCookie
{
    private const COOKIE_NAME = 'device_id';

    private const COOKIE_LIFETIME = 60 * 24 * 365 * 2; // 2 years

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->cookie(self::COOKIE_NAME)) {
            $response->headers->setCookie(
                cookie(
                    name: self::COOKIE_NAME,
                    value: (string) Str::uuid(),
                    minutes: self::COOKIE_LIFETIME,
                    httpOnly: true,
                    secure: config('session.secure', false),
                    sameSite: 'lax',
                )
            );
        }

        return $response;
    }
}
