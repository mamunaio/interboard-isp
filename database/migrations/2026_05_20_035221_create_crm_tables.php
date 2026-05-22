<?php

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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            
            $table->string('status')->default('new'); // new, contacted, survey_pending, proposal_sent, won, lost
            
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('expected_plan_id')->nullable()->constrained('service_plans')->nullOnDelete();
            
            $table->text('notes')->nullable();
            $table->boolean('is_converted')->default(false);
            $table->timestamps();
        });

        Schema::create('lead_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Staff who interacted
            
            $table->string('type')->default('call'); // call, email, meeting, note
            $table->text('summary');
            $table->dateTime('next_follow_up_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_interactions');
        Schema::dropIfExists('leads');
    }
};
