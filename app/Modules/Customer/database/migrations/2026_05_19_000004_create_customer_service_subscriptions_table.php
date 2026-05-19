<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_service_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('service_package_id')->constrained('service_packages')->onDelete('restrict');
            $table->string('subscription_code', 50);
            $table->string('service_type_snapshot', 40); // Immutable snapshot derived from linked package
            $table->string('status', 30)->default('pending'); // pending, active, suspended, disconnected, terminated
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
            $table->unique(['tenant_id', 'subscription_code']);
        });

        Schema::create('subscription_scheduled_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('customer_service_subscription_id')->constrained('customer_service_subscriptions')->onDelete('cascade');
            $table->foreignId('target_service_package_id')->constrained('service_packages')->onDelete('restrict');
            $table->date('scheduled_for');
            $table->json('payload')->nullable();
            $table->string('status', 30)->default('pending'); // pending, executed, cancelled
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_service_subscription_id']);
        });

        Schema::create('subscription_code_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('year', 4);
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_code_sequences');
        Schema::dropIfExists('subscription_scheduled_changes');
        Schema::dropIfExists('customer_service_subscriptions');
    }
};
