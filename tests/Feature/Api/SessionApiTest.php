<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Tests\Feature\Api;

use EpicAlgorithms\AuthSessions\Constants\SessionKey;
use EpicAlgorithms\AuthSessions\Enums\DeviceType;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Tests\TestUser;

class SessionApiTest extends ApiTestCase
{
    public function test_index_lists_active_sessions_in_the_v2_envelope(): void
    {
        $user = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $this->makeSession($user);
        $this->makeSession($user);
        // A revoked session must not appear.
        $this->makeSession($user, ['revoked_at' => now(), 'revoke_reason_id' => 2]);

        $response = $this->actingAs($user)->getJson('/api/account/sessions');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'device_type_id', 'ip_address', 'is_active', 'last_seen_at']],
            'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']],
            'response' => ['status', 'status_code', 'message', 'error_code'],
        ]);
        $response->assertJsonPath('response.status', 'success');
        $response->assertJsonPath('meta.pagination.total', 2);
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_can_filter_by_device_type(): void
    {
        $user = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $this->makeSession($user, ['device_type_id' => DeviceType::DESKTOP]);
        $this->makeSession($user, ['device_type_id' => DeviceType::MOBILE]);

        $response = $this->actingAs($user)
            ->getJson('/api/account/sessions?filter[device_type_id]='.DeviceType::MOBILE);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.device_type_id', DeviceType::MOBILE);
    }

    public function test_show_returns_an_owned_session(): void
    {
        $user = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $session = $this->makeSession($user);

        $response = $this->actingAs($user)->getJson('/api/account/sessions/'.$session->id);

        $response->assertOk();
        $response->assertJsonPath('data.id', $session->id);
        $response->assertJsonPath('response.status', 'success');
    }

    public function test_show_of_another_users_session_yields_404(): void
    {
        $owner = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $attacker = TestUser::create(['email' => 'attacker@test.local', 'password' => 'x']);
        $session = $this->makeSession($owner);

        $response = $this->actingAs($attacker)->getJson('/api/account/sessions/'.$session->id);

        $response->assertNotFound();
    }

    public function test_destroy_revokes_an_owned_session(): void
    {
        $user = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $session = $this->makeSession($user);

        $response = $this->actingAs($user)->deleteJson('/api/account/sessions/'.$session->id);

        $response->assertNoContent();
        $this->assertNotNull($session->fresh()->revoked_at);
    }

    public function test_destroy_of_another_users_session_yields_404_and_leaves_row_untouched(): void
    {
        $owner = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $attacker = TestUser::create(['email' => 'attacker@test.local', 'password' => 'x']);
        $session = $this->makeSession($owner);

        $response = $this->actingAs($attacker)->deleteJson('/api/account/sessions/'.$session->id);

        $response->assertNotFound();
        $fresh = $session->fresh();
        $this->assertNull($fresh->revoked_at);
        $this->assertNull($fresh->revoke_reason_id);
    }

    public function test_destroy_others_revokes_all_but_the_current_session(): void
    {
        $user = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $current = $this->makeSession($user);
        $other1 = $this->makeSession($user);
        $other2 = $this->makeSession($user);

        $response = $this->actingAs($user)
            ->withSession([SessionKey::AUTH_SESSION_ID => $current->id])
            ->deleteJson('/api/account/sessions');

        $response->assertOk();
        $response->assertJsonPath('data.revoked', 2);
        $this->assertNull($current->fresh()->revoked_at);
        $this->assertNotNull($other1->fresh()->revoked_at);
        $this->assertNotNull($other2->fresh()->revoked_at);
    }

    public function test_guest_gets_401(): void
    {
        $response = $this->getJson('/api/account/sessions');

        $response->assertUnauthorized();
    }
}
