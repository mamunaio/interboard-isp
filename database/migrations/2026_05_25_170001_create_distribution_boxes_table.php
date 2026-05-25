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
        Schema::create('distribution_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pop_id')->constrained('pops')->cascadeOnDelete();
            $table->string('name');
            $table->string('location_details')->nullable();
            $table->integer('port_capacity')->default(8);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_boxes');
    }
};
