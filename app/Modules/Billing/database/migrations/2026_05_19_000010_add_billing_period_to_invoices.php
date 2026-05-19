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
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('billing_period_start')->nullable()->after('status');
            $table->date('billing_period_end')->nullable()->after('billing_period_start');

            // Unique index to prevent duplicate invoice generation for the same subscription period
            $table->unique(
                ['tenant_id', 'customer_service_subscription_id', 'billing_period_start', 'billing_period_end'],
                'inv_tenant_sub_period_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('inv_tenant_sub_period_unique');
            $table->dropColumn(['billing_period_start', 'billing_period_end']);
        });
    }
};
