<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->string('reason')->nullable();
            
            // Polymorphic relation to track WHO changed the status (User, SystemJob, Admin)
            $table->nullableMorphs('changed_by');
            
            $table->text('metadata')->nullable(); // Any additional context
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_lifecycle_events');
    }
};
