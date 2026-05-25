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
        Schema::table('tickets', function (Blueprint $table) {
            // priority already exists in table, so we just add the SLA/Resolved fields
            $table->timestamp('sla_due_at')->nullable()->after('priority');
            $table->timestamp('resolved_at')->nullable()->after('sla_due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['sla_due_at', 'resolved_at']);
        });
    }
};
