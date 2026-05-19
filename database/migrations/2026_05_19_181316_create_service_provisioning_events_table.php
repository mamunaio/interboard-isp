<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_provisioning_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('customer_service_subscriptions')->cascadeOnDelete();
            
            $table->string('action_type', 50)->index();
            $table->string('status', 30)->index()->default('pending');
            $table->string('provider', 100);
            
            $table->json('request_payload_json')->nullable();
            $table->json('response_payload_json')->nullable();
            $table->text('error_message')->nullable();
            
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('executed_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_provisioning_events');
    }
};
