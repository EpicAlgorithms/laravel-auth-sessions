<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Tests\Feature;

use EpicAlgorithms\AuthSessions\Enums\LoginMethod;
use EpicAlgorithms\AuthSessions\Enums\SessionRevokeReason;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Tests\TestCase;
use EpicAlgorithms\AuthSessions\Tests\TestUser;

class AuthSessionTest extends TestCase
{
    public function test_it_belongs_to_a_user(): void
    {
        $user = TestUser::create(['email' => 'u@test.local', 'password' => 'x']);

        $session = $this->makeSession($user);

        $this->assertTrue($session->user->is($user));
    }

    public function test_is_active_returns_true_when_not_expired_or_revoked(): void
    {
        $session = $this->makeSession(null, [
            'expires_at' => now()->addMinutes(60),
            'revoked_at' => null,
        ]);

        $this->assertTrue($session->isActive());
    }

    public function test_is_expired_returns_true_past_expires_at(): void
    {
        $session = $this->makeSession(null, [
            'expires_at' => now()->subMinute(),
            'revoked_at' => null,
        ]);

        $this->assertTrue($session->isExpired());
    }

    public function test_is_revoked_returns_true_when_revoked_at_is_set(): void
    {
        $session = $this->makeSession(null, [
            'revoked_at' => now(),
            'revoke_reason_id' => SessionRevokeReason::USER_LOGOUT,
        ]);

        $this->assertTrue($session->isRevoked());
    }

    public function test_revoke_sets_revoked_at_and_reason(): void
    {
        $session = $this->makeSession();

        $session->revoke(SessionRevokeReason::USER_LOGOUT);

        $fresh = $session->fresh();
        $this->assertNotNull($fresh->revoked_at);
        $this->assertSame(SessionRevokeReason::USER_LOGOUT, $fresh->revoke_reason_id);
    }

    private function makeSession(?TestUser $user = null, array $overrides = []): AuthSession
    {
        $user ??= TestUser::create(['email' => 'u'.uniqid().'@test.local', 'password' => 'x']);

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
