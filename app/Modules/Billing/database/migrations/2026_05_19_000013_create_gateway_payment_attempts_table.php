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
        Schema::create('gateway_payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->string('gateway', 30);
            $table->string('attempt_reference', 100)->unique();
            $table->string('gateway_transaction_id', 150)->nullable();
            $table->decimal('requested_amount', 14, 4);
            $table->string('currency', 3)->default('BDT');
            $table->string('status', 30);
            $table->json('gateway_payload')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('payment_receipt_id')->nullable()->constrained('payment_receipts')->onDelete('restrict');
            $table->timestamps();

            // Guard against duplicate processing of transaction IDs per gateway per tenant
            $table->unique(['tenant_id', 'gateway', 'gateway_transaction_id'], 'gateway_tx_unique');
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateway_payment_attempts');
    }
};
