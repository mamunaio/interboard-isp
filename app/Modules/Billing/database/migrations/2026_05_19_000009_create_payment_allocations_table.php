<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add amount_paid and amount_due columns to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('amount_paid', 14, 4)->default('0.0000')->after('grand_total');
            $table->decimal('amount_due', 14, 4)->default('0.0000')->after('amount_paid');
        });

        // 2. Deterministically backfill existing records to prevent data anomalies
        DB::table('invoices')->where('status', 'draft')->update([
            'amount_paid' => '0.0000',
            'amount_due' => '0.0000',
        ]);

        DB::table('invoices')->where('status', 'issued')->update([
            'amount_paid' => '0.0000',
            'amount_due' => DB::raw('grand_total'),
        ]);

        DB::table('invoices')->where('status', 'paid')->update([
            'amount_paid' => DB::raw('grand_total'),
            'amount_due' => '0.0000',
        ]);

        DB::table('invoices')->whereIn('status', ['void', 'cancelled'])->update([
            'amount_paid' => '0.0000',
            'amount_due' => '0.0000',
        ]);

        // 3. Create the payment_allocations table with restrictive foreign keys
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('restrict');
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->onDelete('restrict');
            $table->foreignId('ledger_entry_id')->nullable()->constrained('ledger_entries')->onDelete('restrict');
            $table->decimal('allocation_amount', 14, 4)->default('0.0000');
            $table->unsignedInteger('allocation_order');
            $table->string('idempotency_key', 255)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Strict Idempotency Uniqueness scope
            $table->unique(['tenant_id', 'wallet_id', 'idempotency_key'], 'pa_tenant_wallet_idempotency_unique');
            $table->index(['tenant_id', 'invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'amount_due']);
        });
    }
};
