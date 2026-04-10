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
        if (Schema::hasTable('auth_devices')) {
            return;
        }

        Schema::create('auth_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('device_id');
            $table->timestamp('requires_reauth_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
            $table->index(['user_id', 'requires_reauth_at']);
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
