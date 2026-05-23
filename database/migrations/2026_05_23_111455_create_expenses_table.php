<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\Auth\Models\StaffUser; // Or User depending on the admin model

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->cascadeOnDelete();
            
            $table->foreignId('user_id')->constrained('staff_users')->cascadeOnDelete();
            
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
