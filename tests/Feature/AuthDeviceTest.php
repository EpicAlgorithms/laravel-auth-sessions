<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Tests\Feature;

use EpicAlgorithms\AuthSessions\Models\AuthDevice;
use EpicAlgorithms\AuthSessions\Tests\TestCase;
use EpicAlgorithms\AuthSessions\Tests\TestUser;
use Illuminate\Database\QueryException;

class AuthDeviceTest extends TestCase
{
    public function test_it_belongs_to_a_user(): void
    {
        $user = TestUser::create(['email' => 'u@test.local', 'password' => 'x']);

        $device = AuthDevice::create([
            'user_id' => $user->id,
            'device_id' => '550e8400-e29b-41d4-a716-446655440000',
        ]);

        $this->assertTrue($device->user->is($user));
    }

    public function test_it_enforces_unique_user_device_pair(): void
    {
        $user = TestUser::create(['email' => 'u@test.local', 'password' => 'x']);

        AuthDevice::create([
            'user_id' => $user->id,
            'device_id' => 'dup-device-id',
        ]);

        $this->expectException(QueryException::class);

        AuthDevice::create([
            'user_id' => $user->id,
            'device_id' => 'dup-device-id',
        ]);
    }
}
