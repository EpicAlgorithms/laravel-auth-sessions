<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Tests\Feature;

use EpicAlgorithms\AuthSessions\Enums\LoginMethod;
use EpicAlgorithms\AuthSessions\Http\Controllers\BaseSessionsController;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Tests\TestCase;
use EpicAlgorithms\AuthSessions\Tests\TestUser;

class SessionsControllerTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // The `web` middleware group encrypts cookies, so a key is required.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Point the package routes at a concrete controller for the duration
        // of these tests. This must happen before the provider boots (which is
        // when routes are registered), so it lives in defineEnvironment.
        $app['config']->set('auth-sessions.controller', SessionsTestController::class);
    }

    public function test_owner_can_revoke_their_own_session_and_sees_success(): void
    {
        $user = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $session = $this->makeSession($user);

        $response = $this->actingAs($user)
            ->from('/settings/sessions')
            ->delete('/settings/sessions/'.$session->id);

        $response->assertRedirect('/settings/sessions');
        $response->assertSessionHas('status');
        $response->assertSessionMissing('error');

        $this->assertNotNull($session->fresh()->revoked_at);
    }

    public function test_revoking_another_users_session_yields_404_and_leaves_row_untouched(): void
    {
        $owner = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);
        $attacker = TestUser::create(['email' => 'attacker@test.local', 'password' => 'x']);
        $session = $this->makeSession($owner);

        $response = $this->actingAs($attacker)
            ->from('/settings/sessions')
            ->delete('/settings/sessions/'.$session->id);

        $response->assertNotFound();

        $fresh = $session->fresh();
        $this->assertNull($fresh->revoked_at);
        $this->assertNull($fresh->revoke_reason_id);
    }

    public function test_revoking_unknown_session_yields_404(): void
    {
        $user = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);

        $response = $this->actingAs($user)
            ->from('/settings/sessions')
            ->delete('/settings/sessions/01HZZZZZZZZZZZZZZZZZZZZZZZ');

        $response->assertNotFound();
        $this->assertSame(0, AuthSession::whereNotNull('revoked_at')->count());
    }

    private function makeSession(TestUser $user, array $overrides = []): AuthSession
    {
        return AuthSession::create(array_merge([
            'user_id' => $user->id,
            'login_method_id' => LoginMethod::PASSWORD,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(120),
            'created_at' => now(),
        ], $overrides));
    }
}

/**
 * Minimal concrete controller so the abstract BaseSessionsController can be
 * exercised through the package's real routes and owner-scoped route binding.
 */
class SessionsTestController extends BaseSessionsController
{
}
