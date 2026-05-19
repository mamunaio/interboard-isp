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
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('code', 50);
            $table->string('tax_type', 30); // percentage, fixed
            $table->string('calculation_mode', 30); // exclusive, inclusive
            $table->boolean('is_compound')->default(false);
            $table->integer('compound_order')->nullable();
            $table->decimal('rate', 14, 4)->default('0.0000');
            $table->decimal('fixed_amount', 14, 4)->default('0.0000');
            $table->json('applies_to_invoice_types')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
