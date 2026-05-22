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
        Schema::create('olt_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('ip_address');
            $table->integer('port')->default(23);
            $table->string('username');
            $table->string('password');
            $table->string('vendor')->default('zte'); // zte, huawei, fiberhome, etc.
            $table->string('status')->default('active'); // active, inactive, offline
            $table->timestamps();
        });

        Schema::create('onu_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('customer_service_subscriptions')->nullOnDelete();
            $table->foreignId('olt_device_id')->constrained('olt_devices')->cascadeOnDelete();
            $table->string('serial_number')->index();
            $table->string('gpon_port')->default('0/1/1');
            $table->integer('onu_id')->nullable();
            $table->string('line_profile')->nullable();
            $table->string('service_profile')->nullable();
            $table->string('status')->default('pending'); // pending, active, suspended, deprovisioned
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onu_devices');
        Schema::dropIfExists('olt_devices');
    }
};
