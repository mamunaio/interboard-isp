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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('category')->default('accessories'); // onu, router, switch, cable, accessories
            $table->boolean('track_serial')->default(false);
            $table->integer('low_stock_threshold')->default(5);
            $table->integer('current_stock')->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('serial_number');
            $table->string('mac_address')->nullable();
            $table->string('status')->default('in_stock'); // in_stock, deployed, with_technician, defective, retired, pending_recovery
            $table->timestamps();
            
            $table->unique(['inventory_item_id', 'serial_number']);
        });

        Schema::create('inventory_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_serial_id')->constrained('inventory_serials')->cascadeOnDelete();
            $table->morphs('assigned_to'); // User or Customer
            $table->dateTime('assigned_at');
            $table->dateTime('returned_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_assignments');
        Schema::dropIfExists('inventory_serials');
        Schema::dropIfExists('inventory_items');
    }
};
