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
        Schema::create('suspension_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->foreignId('customer_service_subscription_id')->constrained('customer_service_subscriptions')->onDelete('restrict');
            $table->string('action_type', 30); // suspended, reactivated
            $table->text('reason');
            $table->json('triggering_metadata')->nullable();
            $table->foreignId('reconnection_invoice_id')->nullable()->constrained('invoices')->onDelete('restrict');
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'customer_service_subscription_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspension_events');
    }
};
