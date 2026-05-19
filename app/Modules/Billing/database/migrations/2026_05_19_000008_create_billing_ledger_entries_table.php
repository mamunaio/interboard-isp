<?php

declare(strict_types=1);

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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('restrict');
            $table->string('transaction_type');
            $table->string('direction');
            $table->decimal('amount', 14, 4);
            $table->decimal('balance_before', 14, 4);
            $table->decimal('balance_after', 14, 4);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'wallet_id', 'idempotency_key'], 'ledger_idempotency_unique');
            $table->index(['tenant_id', 'wallet_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['created_by_type', 'created_by_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
