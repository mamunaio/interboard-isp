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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');
            $table->string('currency', 3);
            $table->decimal('current_balance', 14, 4)->default('0.0000');
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'owner_type', 'owner_id', 'currency'], 'wallets_owner_currency_unique');
            $table->index(['tenant_id', 'owner_type', 'owner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
