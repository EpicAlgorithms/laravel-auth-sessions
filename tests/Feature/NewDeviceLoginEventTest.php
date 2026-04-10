<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Tests\Feature;

use EpicAlgorithms\AuthSessions\Enums\LoginMethod;
use EpicAlgorithms\AuthSessions\Events\NewDeviceLogin;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Tests\TestCase;
use EpicAlgorithms\AuthSessions\Tests\TestUser;
use Illuminate\Support\Facades\Event;

class NewDeviceLoginEventTest extends TestCase
{
    public function test_event_carries_user_and_session(): void
    {
        Event::fake();

        $user = TestUser::create(['email' => 'u@test.local', 'password' => 'x']);
        $session = AuthSession::create([
            'user_id' => $user->id,
            'login_method_id' => LoginMethod::PASSWORD,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(120),
            'created_at' => now(),
        ]);

        event(new NewDeviceLogin($user, $session, '127.0.0.1', 'PHPUnit'));

        Event::assertDispatched(NewDeviceLogin::class, function ($e) use ($user) {
            return $e->user->is($user) && $e->ip === '127.0.0.1';
        });
    }
}
