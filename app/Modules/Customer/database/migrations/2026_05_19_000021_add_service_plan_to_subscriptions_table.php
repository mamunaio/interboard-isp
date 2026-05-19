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
        Schema::table('customer_service_subscriptions', function (Blueprint $table) {
            $table->foreignId('service_plan_id')->nullable()->after('service_package_id')->constrained('service_plans')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_service_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['service_plan_id']);
            $table->dropColumn('service_plan_id');
        });
    }
};
