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
        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('type', 40); // national_id, passport, etc.
            $table->string('title');
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('storage_disk', 50)->default('local'); // private storage only!
            $table->string('checksum_hash', 64); // SHA-256 of file contents
            $table->string('verification_status', 30)->default('pending'); // pending, approved, etc.
            $table->foreignId('verified_by')->nullable()->constrained('staff_users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('expiry_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
            // Customer-scoped duplicate detection index
            $table->unique(['customer_id', 'checksum_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
    }
};
