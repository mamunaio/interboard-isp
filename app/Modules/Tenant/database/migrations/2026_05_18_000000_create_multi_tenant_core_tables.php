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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price_monthly', 12, 2);
            $table->decimal('price_yearly', 12, 2);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('feature_key');
            $table->string('feature_value');
            $table->timestamps();
            $table->unique(['plan_id', 'feature_key']);
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('status', 50)->default('trial'); // 'trial', 'active', 'suspended', 'terminated'
            $table->foreignId('plan_id')->constrained('plans');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspended_reason')->nullable();
            $table->string('timezone', 100)->default('Asia/Dhaka');
            $table->string('currency', 10)->default('BDT');
            $table->timestamps();
        });

        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->string('ssl_status', 50)->default('pending'); // 'pending', 'active', 'failed'
            $table->timestamps();
        });

        Schema::create('tenant_branding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('logo_path', 500)->nullable();
            $table->string('dark_logo_path', 500)->nullable();
            $table->string('favicon_path', 500)->nullable();
            $table->string('primary_color', 10)->default('#00a8ff');
            $table->string('secondary_color', 10)->default('#9c88ff');
            $table->text('custom_css')->nullable();
            $table->timestamps();
            $table->unique('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_branding');
        Schema::dropIfExists('tenant_domains');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
