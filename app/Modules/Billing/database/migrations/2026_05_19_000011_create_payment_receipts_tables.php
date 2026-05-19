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
        // 1. Race-Safe Receipt Sequence Generator
        Schema::create('receipt_number_sequences', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->unsignedInteger('year');
            $table->unsignedInteger('last_value')->default(0);
            $table->timestamps();

            $table->primary(['tenant_id', 'year']);
        });

        // 2. Immutable Payment Receipts Document Table
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->string('receipt_number', 50);
            $table->string('payment_method', 30); // cash, bank_transfer, mobile_banking_manual, manual_adjustment
            $table->decimal('amount', 14, 4);
            $table->string('currency', 3)->default('BDT');
            $table->string('reference', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('posted'); // posted
            $table->foreignId('wallet_ledger_entry_id')->constrained('ledger_entries')->onDelete('restrict');
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Uniqueness and Performance Indexing
            $table->unique(['tenant_id', 'receipt_number']);
            $table->unique(['tenant_id', 'payment_method', 'reference'], 'pr_tenant_method_ref_unique');
            $table->index(['tenant_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
        Schema::dropIfExists('receipt_number_sequences');
    }
};
