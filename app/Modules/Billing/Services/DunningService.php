<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DunningService
{
    /**
     * Evaluate all overdue invoices and send appropriate reminders based on aging.
     */
    public function processDunning(int $tenantId): void
    {
        // Find overdue invoices that haven't been fully paid
        $invoices = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'overdue')
            ->where('amount_due', '>', 0)
            ->get();

        foreach ($invoices as $invoice) {
            $daysOverdue = now()->startOfDay()->diffInDays($invoice->due_date->startOfDay());

            $level = this->determineDunningLevel($daysOverdue);

            if ($level > 0 && !$this->hasDunningEventBeenSent($invoice->id, $level)) {
                $this->triggerDunningAction($invoice, $level);
            }
        }
    }

    protected function determineDunningLevel(int $daysOverdue): int
    {
        if ($daysOverdue == 1) return 1; // Gentle Reminder
        if ($daysOverdue == 3) return 2; // Warning
        if ($daysOverdue == 5) return 3; // Final Notice / Suspension Warning
        return 0;
    }

    protected function hasDunningEventBeenSent(int $invoiceId, int $level): bool
    {
        return DB::table('dunning_events')
            ->where('invoice_id', $invoiceId)
            ->where('dunning_level', $level)
            ->exists();
    }

    protected function triggerDunningAction(Invoice $invoice, int $level): void
    {
        $message = "Friendly Reminder: Your invoice {$invoice->invoice_number} is overdue.";
        if ($level === 2) {
            $message = "Warning: Invoice {$invoice->invoice_number} is 3 days overdue. Please pay immediately.";
        } elseif ($level === 3) {
            $message = "Final Notice: Service will be suspended if {$invoice->invoice_number} is not paid today.";
        }

        // In a real app, dispatch an SMS/Email job here.
        Log::info("Dunning Level {$level} sent to Customer {$invoice->customer_id} for Invoice {$invoice->invoice_number}");

        DB::table('dunning_events')->insert([
            'tenant_id' => $invoice->tenant_id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'dunning_level' => $level,
            'communication_channel' => 'sms',
            'sent_at' => now(),
            'message_payload' => $message,
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
