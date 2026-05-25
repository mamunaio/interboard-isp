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
        Schema::create('scheduled_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('action_type'); // e.g., 'suspend', 'reactivate', 'change_package'
            $table->date('target_date');
            $table->json('payload')->nullable(); // For package changes or details
            $table->string('status')->default('pending'); // pending, executed, failed
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_actions');
    }
};
