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
        // 1. Alter payment_allocations to add explicit relational linkage to payment_receipts
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->foreignId('payment_receipt_id')
                ->nullable()
                ->constrained('payment_receipts')
                ->onDelete('restrict');
        });

        // 2. Immutable Receipt and Adjustment Audit Trail
        Schema::create('reversal_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->string('original_entity_type', 150);
            $table->unsignedBigInteger('original_entity_id');
            $table->string('reversal_type', 50); // payment_receipt_reversal, credit_adjustment, etc.
            $table->decimal('amount', 14, 4);
            $table->string('currency', 3)->default('BDT');
            $table->text('reason');
            $table->foreignId('reversal_ledger_entry_id')->constrained('ledger_entries')->onDelete('restrict');
            $table->string('idempotency_key', 100)->nullable();
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'original_entity_type', 'original_entity_id'], 'reversal_entity_index');
            $table->unique(['tenant_id', 'idempotency_key'], 'reversal_idempotency_unique');
        });

        // 3. Immutable Payment Allocation Reversals Table
        Schema::create('payment_allocation_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->foreignId('payment_allocation_id')->constrained('payment_allocations')->onDelete('restrict');
            $table->decimal('amount', 14, 4);
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'payment_allocation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocation_reversals');
        Schema::dropIfExists('reversal_events');

        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropForeign(['payment_receipt_id']);
            $table->dropColumn('payment_receipt_id');
        });
    }
};
