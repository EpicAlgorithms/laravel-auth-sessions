<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Tests\Feature\Api;

use EpicAlgorithms\AuthSessions\Tests\TestCase;
use EpicAlgorithms\AuthSessions\Tests\TestUser;
use Illuminate\Support\Facades\Route;

/**
 * With the default `headless` HTTP mode the JSON API must not exist, while the
 * package's web session-manager routes remain registered and untouched.
 */
class HeadlessModeTest extends TestCase
{
    public function test_api_routes_are_not_registered_in_headless_mode(): void
    {
        $this->assertFalse(Route::has('auth-sessions.api.sessions.index'));
        $this->assertFalse(Route::has('auth-sessions.api.devices.index'));

        $user = TestUser::create(['email' => 'owner@test.local', 'password' => 'x']);

        $this->actingAs($user)
            ->getJson('/api/account/sessions')
            ->assertNotFound();
    }

    public function test_web_routes_remain_registered_in_headless_mode(): void
    {
        $this->assertTrue(Route::has('auth-sessions.index'));
        $this->assertTrue(Route::has('auth-sessions.destroy'));
        $this->assertTrue(Route::has('auth-sessions.destroy-others'));
    }
}
