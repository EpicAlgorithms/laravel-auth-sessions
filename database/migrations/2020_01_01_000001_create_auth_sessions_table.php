<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('auth_sessions')) {
            return;
        }

        Schema::create('auth_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('device_id')->nullable();
            $table->string('laravel_session_id', 255)->nullable();
            $table->tinyInteger('login_method_id');
            $table->tinyInteger('device_type_id')->nullable();
            $table->string('os_name', 100)->nullable();
            $table->string('os_version', 50)->nullable();
            $table->string('browser_name', 100)->nullable();
            $table->string('browser_version', 50)->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->boolean('is_remembered')->default(false);
            $table->timestamp('last_seen_at');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->tinyInteger('revoke_reason_id')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'revoked_at', 'expires_at']);
            $table->index(['user_id', 'last_seen_at']);
            $table->index('laravel_session_id');
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentional no-op. Downgrading is a consumer concern.
    }
};
