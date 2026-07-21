<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Tests\Feature;

use EpicAlgorithms\AuthSessions\Enums\LoginMethod;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Tests\TestCase;
use EpicAlgorithms\AuthSessions\Tests\TestUser;

class PruneAuthSessionsCommandTest extends TestCase
{
    public function test_prune_deletes_sessions_expired_beyond_the_grace_period(): void
    {
        $user = TestUser::create(['email' => 'u@test.local', 'password' => 'x']);

        $stale = $this->makeSession($user, ['expires_at' => now()->subDays(31)]);
        $withinGrace = $this->makeSession($user, ['expires_at' => now()->subDays(5)]);
        $active = $this->makeSession($user, ['expires_at' => now()->addMinutes(120)]);

        $this->artisan('auth-sessions:prune')
            ->assertExitCode(0);

        $this->assertNull(AuthSession::find($stale->id));
        $this->assertNotNull(AuthSession::find($withinGrace->id));
        $this->assertNotNull(AuthSession::find($active->id));
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
