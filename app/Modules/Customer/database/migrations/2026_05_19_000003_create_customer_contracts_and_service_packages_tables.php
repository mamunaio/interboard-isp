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
        Schema::create('customer_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('contract_number', 50);
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->integer('renewal_term_days')->default(365);
            $table->decimal('early_termination_fee', 12, 2)->default(0.00);
            $table->string('status', 30)->default('draft'); // draft, active, expired, terminated
            $table->json('terms_snapshot')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
            $table->unique(['tenant_id', 'contract_number']);
        });

        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('package_code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('service_type', 40); // broadband, etc.
            $table->json('customer_type_compatibility')->nullable(); // residential, business, enterprise
            $table->unsignedInteger('download_speed_kbps');
            $table->unsignedInteger('upload_speed_kbps');
            $table->unsignedInteger('burst_download_kbps')->nullable();
            $table->unsignedInteger('burst_upload_kbps')->nullable();
            $table->unsignedInteger('burst_threshold_kb')->nullable();
            $table->unsignedInteger('burst_time_seconds')->nullable();
            $table->string('mikrotik_profile_name')->nullable();
            $table->string('radius_profile_name')->nullable();
            $table->string('gpon_line_profile')->nullable();
            $table->string('gpon_service_profile')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'package_code']);
        });

        Schema::create('contract_number_sequences', function (Blueprint $table) {
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
        Schema::dropIfExists('contract_number_sequences');
        Schema::dropIfExists('service_packages');
        Schema::dropIfExists('customer_contracts');
    }
};
