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
        Schema::create('webhook_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('url', 500);
            $table->string('secret_key', 255);
            $table->json('events'); // e.g. ["ticket.created", "customer.suspended"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('webhook_config_id')->constrained('webhook_configs')->onDelete('cascade');
            $table->string('event');
            $table->string('url', 500);
            $table->json('payload');
            $table->string('signature', 64);
            $table->integer('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->string('status', 50)->default('pending'); // 'pending', 'success', 'failed'
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhook_configs');
    }
};
