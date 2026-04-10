<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Tests;

use EpicAlgorithms\AuthSessions\AuthSessionsServiceProvider;
use EpicAlgorithms\AuthSessions\Concerns\HasAuthSessions;
use EpicAlgorithms\AuthSessions\Enums\DeviceType;
use EpicAlgorithms\AuthSessions\Enums\LoginMethod;
use EpicAlgorithms\AuthSessions\Enums\SessionRevokeReason;
use EpicAlgorithms\EnumModel\EnumModelServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            EnumModelServiceProvider::class,
            AuthSessionsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth-sessions.user_model', TestUser::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Create minimal users table BEFORE the package's auth_sessions migration
        // (auth_sessions has a foreign key on users).
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        // Run package migrations (auth_sessions, auth_devices).
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Explicitly (re-)create and seed the EnumModel tables. The
        // laravel-enum-models package uses a boot hook + cache gate to auto
        // migrate, which is unreliable in a testbench environment where the
        // database is rebuilt per test but the Eloquent boot fires only once
        // per PHP process. Calling migrateAndSeed() directly guarantees the
        // tables exist for every test.
        (new DeviceType)->migrateAndSeed();
        (new LoginMethod)->migrateAndSeed();
        (new SessionRevokeReason)->migrateAndSeed();
    }
}

class TestUser extends Authenticatable
{
    use HasAuthSessions;
    use HasFactory;

    protected $table = 'users';

    protected $guarded = [];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];
}
