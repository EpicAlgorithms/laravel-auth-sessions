<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Tests\Feature\Api;

use EpicAlgorithms\AuthSessions\Enums\DeviceType;
use EpicAlgorithms\AuthSessions\Enums\LoginMethod;
use EpicAlgorithms\AuthSessions\Models\AuthDevice;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Tests\TestCase;
use EpicAlgorithms\AuthSessions\Tests\TestUser;

/**
 * Base for the JSON API feature tests: boots the module in `api` HTTP mode so
 * the api-kit route group and endpoints are registered, and offers small
 * factory helpers for owned sessions / devices.
 */
abstract class ApiTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Opt the module into its JSON API surface. Must be set before the
        // provider boots (which is when routes are registered).
        $app['config']->set('auth-sessions.http.mode', 'api');
    }

    protected function makeSession(TestUser $user, array $overrides = []): AuthSession
    {
        return AuthSession::create(array_merge([
            'user_id' => $user->id,
            'login_method_id' => LoginMethod::PASSWORD,
            'device_type_id' => DeviceType::DESKTOP,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(120),
            'created_at' => now(),
        ], $overrides));
    }

    protected function makeDevice(TestUser $user, array $overrides = []): AuthDevice
    {
        return AuthDevice::create(array_merge([
            'user_id' => $user->id,
            'device_id' => (string) \Illuminate\Support\Str::uuid(),
        ], $overrides));
    }
}
