<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('account_code', 20); // e.g. 1000, 2000, 4000
            $table->string('account_name', 100); // e.g. Cash, Accounts Receivable, Internet Revenue
            $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'account_code']);
        });

        Schema::create('financial_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('period_name', 50); // e.g. Jan 2026
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();

            $table->unique(['tenant_id', 'start_date', 'end_date']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('reference_number', 50)->nullable()->index(); // e.g. INV-001, PAY-001
            $table->morphs('source'); // Connects to Invoice, PaymentReceipt, etc.
            $table->string('description')->nullable();
            $table->enum('status', ['posted', 'voided'])->default('posted');
            $table->timestamps();
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->decimal('debit', 16, 4)->default(0);
            $table->decimal('credit', 16, 4)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('financial_periods');
        Schema::dropIfExists('chart_of_accounts');
    }
};
