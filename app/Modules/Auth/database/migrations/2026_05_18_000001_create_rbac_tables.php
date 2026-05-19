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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->timestamps();

            // Unique slug per tenant (or global system templates)
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module', 100); // 'network', 'billing', 'tickets'
            $table->string('action', 100); // 'view', 'create', 'edit', 'delete'
            $table->string('slug')->unique(); // 'network.routers.view'
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('staff_user_roles', function (Blueprint $table) {
            $table->foreignId('staff_user_id')->constrained('staff_users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->primary(['staff_user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
