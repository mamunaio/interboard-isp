<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Credit Memos
        Schema::create('credit_memos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('memo_number', 50)->unique();
            $table->decimal('amount', 10, 4);
            $table->string('reason');
            $table->enum('status', ['issued', 'applied', 'voided'])->default('issued');
            $table->timestamps();
        });

        // 2. Tax Remittances
        Schema::create('tax_remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_tax_collected', 16, 4)->default(0);
            $table->decimal('total_tax_remitted', 16, 4)->default(0);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_remittances');
        Schema::dropIfExists('credit_memos');
    }
};
