<?php

declare(strict_types=1);

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
        Schema::create('staff_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('status', 50)->default('active'); // 'active', 'inactive'
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();

            // Enforce email uniqueness ONLY within the scope of each tenant
            $table->unique(['tenant_id', 'email']);
        });

        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 50);
            $table->string('code_hash');
            $table->string('purpose', 50); // 'login', '2fa', 'reset'
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'expires_at']);
        });

        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->string('user_type'); // 'staff', 'customer'
            $table->unsignedBigInteger('user_id');
            $table->string('device_hash', 64); // SHA256 of IP + User-Agent
            $table->string('ip_address', 45);
            $table->string('user_agent', 500);
            $table->timestamp('last_active_at');
            $table->timestamps();

            $table->unique(['user_type', 'user_id', 'device_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('staff_users');
    }
};
