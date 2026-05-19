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
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('type', 30); // billing, installation, both
            $table->string('label', 100); // Home, Office, HQ, Branch
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city', 100);
            $table->string('state_region', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100);
            $table->string('landmark')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_default_billing')->default(false);
            $table->boolean('is_default_installation')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
        });

        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('full_name');
            $table->string('designation')->nullable();
            $table->string('phone', 50);
            $table->string('alternate_phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('type', 30); // general, billing, technical, emergency
            $table->text('notes')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_contacts');
        Schema::dropIfExists('customer_addresses');
    }
};
