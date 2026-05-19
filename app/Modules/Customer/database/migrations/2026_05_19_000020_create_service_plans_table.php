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
        Schema::create('service_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('plan_code', 50);
            $table->string('name');
            $table->decimal('monthly_price', 14, 4)->default('0.0000');
            $table->string('billing_cycle', 30)->default('monthly'); // monthly, yearly, etc.
            $table->string('download_speed', 50); // e.g. "10 Mbps"
            $table->string('upload_speed', 50);   // e.g. "5 Mbps"
            $table->foreignId('tax_rule_id')->nullable()->constrained('tax_rules')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'plan_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_plans');
    }
};
