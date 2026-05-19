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
        // Add scheduler metadata to customer_service_subscriptions
        Schema::table('customer_service_subscriptions', function (Blueprint $table) {
            $table->string('billing_mode', 20)->default('prepaid')->after('status');
            $table->string('billing_cycle_type', 20)->default('anniversary')->after('billing_mode');
            $table->date('next_billing_date')->nullable()->after('billing_cycle_type');
            $table->timestamp('last_billed_at')->nullable()->after('next_billing_date');
        });

        // Add monthly price column to service_packages
        Schema::table('service_packages', function (Blueprint $table) {
            $table->decimal('price_monthly', 14, 4)->default('0.0000')->after('service_type');
        });

        // Safe deterministic backfill for existing subscription records
        DB::table('customer_service_subscriptions')->update([
            'billing_mode' => 'prepaid',
            'billing_cycle_type' => 'anniversary',
            'next_billing_date' => DB::raw("COALESCE(DATE(activated_at), DATE(created_at))"),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn('price_monthly');
        });

        Schema::table('customer_service_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['billing_mode', 'billing_cycle_type', 'next_billing_date', 'last_billed_at']);
        });
    }
};
