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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('customer_service_subscription_id')->nullable()->constrained('customer_service_subscriptions')->onDelete('set null');
            $table->string('invoice_number', 50);
            $table->string('invoice_type', 40);
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('currency', 3)->default('BDT');
            $table->decimal('subtotal', 14, 4)->default('0.0000');
            $table->decimal('tax_total', 14, 4)->default('0.0000');
            $table->decimal('discount_total', 14, 4)->default('0.0000');
            $table->decimal('grand_total', 14, 4)->default('0.0000');
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
            $table->unique(['tenant_id', 'invoice_number']);
        });

        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('line_type', 40);
            $table->string('description');
            $table->decimal('quantity', 14, 4)->default('1.0000');
            $table->decimal('unit_price', 14, 4)->default('0.0000');
            $table->decimal('line_subtotal', 14, 4)->default('0.0000');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('year', 4);
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_number_sequences');
        Schema::dropIfExists('invoice_line_items');
        Schema::dropIfExists('invoices');
    }
};
