<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Dunning Events (Tracking Collections/Reminders)
        Schema::create('dunning_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->integer('dunning_level'); // 1 = Gentle Reminder, 2 = Warning, 3 = Final Notice
            $table->string('communication_channel'); // sms, email, push
            $table->timestamp('sent_at');
            $table->text('message_payload')->nullable();
            $table->string('status')->default('sent'); // sent, failed
            $table->timestamps();
        });

        // 2. Billing Disputes
        Schema::create('billing_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('dispute_reason');
            $table->decimal('disputed_amount', 10, 4);
            $table->enum('status', ['open', 'under_review', 'resolved_won', 'resolved_lost', 'refunded'])->default('open');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        // 3. Add Credit Limit to Customers for Credit Control
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('credit_limit', 10, 4)->default(0.0000)->after('billing_day');
            $table->boolean('allow_overdue_usage')->default(false)->after('credit_limit');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'allow_overdue_usage']);
        });

        Schema::dropIfExists('billing_disputes');
        Schema::dropIfExists('dunning_events');
    }
};
