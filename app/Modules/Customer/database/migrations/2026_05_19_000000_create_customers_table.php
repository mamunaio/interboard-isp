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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('customer_code', 50);
            $table->string('type', 30); // residential, business, enterprise
            $table->string('full_name');
            $table->string('company_name')->nullable();
            $table->string('phone', 50);
            $table->string('alternate_phone', 50)->nullable();
            $table->text('email')->nullable(); // stored as raw text/nullable
            $table->text('national_id')->nullable(); // encrypted: stored as text
            $table->string('national_id_hash', 64)->nullable(); // SHA-256 blind index for unique search/lookup
            $table->text('business_registration')->nullable(); // encrypted: stored as text
            $table->string('business_registration_hash', 64)->nullable(); // SHA-256 blind index for unique search/lookup
            $table->text('date_of_birth')->nullable(); // encrypted: stored as text
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->string('referral_source')->nullable();
            $table->string('status', 30)->default('lead');
            $table->timestamps();

            // Tenant-scoped unique database constraints
            $table->unique(['tenant_id', 'customer_code']);
            $table->unique(['tenant_id', 'phone']);
            $table->unique(['tenant_id', 'national_id_hash']);
            $table->unique(['tenant_id', 'business_registration_hash']);
        });

        // Searchable, transaction-safe sequence table for customer code generation fallback
        Schema::create('customer_code_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('year', 4);
            $table->unsignedInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_code_sequences');
        Schema::dropIfExists('customers');
    }
};
