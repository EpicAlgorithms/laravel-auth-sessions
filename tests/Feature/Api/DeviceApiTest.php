<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Tests\Feature\Api;

use EpicAlgorithms\AuthSessions\Tests\TestUser;

class DeviceApiTest extends ApiTestCase
{
    public function test_index_lists_owned_devices_in_the_v2_envelope(): void
    {
        $user = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $this->makeDevice($user);
        $this->makeDevice($user);
        // Another user's device must not leak into the list.
        $other = TestUser::create(['email' => 'other@test.local', 'password' => 'x']);
        $this->makeDevice($other);

        $response = $this->actingAs($user)->getJson('/api/account/devices');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'device_id', 'requires_reauth', 'requires_reauth_at']],
            'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']],
            'response' => ['status', 'status_code', 'message', 'error_code'],
        ]);
        $response->assertJsonPath('response.status', 'success');
        $response->assertJsonCount(2, 'data');
    }

    public function test_destroy_forces_reauth_on_an_owned_device(): void
    {
        $user = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $device = $this->makeDevice($user);

        $this->assertNull($device->requires_reauth_at);

        $response = $this->actingAs($user)->deleteJson('/api/account/devices/'.$device->id);

        $response->assertNoContent();
        $this->assertNotNull($device->fresh()->requires_reauth_at);
    }

    public function test_destroy_of_another_users_device_yields_404_and_leaves_row_untouched(): void
    {
        $owner = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $attacker = TestUser::create(['email' => 'attacker@test.local', 'password' => 'x']);
        $device = $this->makeDevice($owner);

        $response = $this->actingAs($attacker)->deleteJson('/api/account/devices/'.$device->id);

        $response->assertNotFound();
        $this->assertNull($device->fresh()->requires_reauth_at);
    }

    public function test_guest_gets_401(): void
    {
        $response = $this->getJson('/api/account/devices');

        $response->assertUnauthorized();
    }
}
