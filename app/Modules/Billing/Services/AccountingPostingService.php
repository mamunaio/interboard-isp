<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\ChartOfAccount;
use App\Modules\Billing\Models\FinancialPeriod;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\JournalEntry;
use App\Modules\Billing\Models\PaymentReceipt;
use Exception;
use Illuminate\Support\Facades\DB;

class AccountingPostingService
{
    /**
     * Post an invoice to the general ledger.
     * Debit: Accounts Receivable (Asset)
     * Credit: Internet Revenue (Revenue)
     */
    public function postInvoice(Invoice $invoice): JournalEntry
    {
        return DB::transaction(function () use ($invoice) {
            $this->ensurePeriodIsOpen($invoice->tenant_id, $invoice->issue_date);

            $arAccount = $this->getOrCreateAccount($invoice->tenant_id, '1200', 'Accounts Receivable', 'asset');
            $revenueAccount = $this->getOrCreateAccount($invoice->tenant_id, '4100', 'Internet Services Revenue', 'revenue');

            $entry = JournalEntry::create([
                'tenant_id' => $invoice->tenant_id,
                'entry_date' => $invoice->issue_date,
                'reference_number' => $invoice->invoice_number,
                'source_type' => Invoice::class,
                'source_id' => $invoice->id,
                'description' => "Invoice generated for {$invoice->invoice_number}",
            ]);

            // Debit AR
            $entry->lines()->create([
                'chart_of_account_id' => $arAccount->id,
                'debit' => $invoice->grand_total,
                'credit' => 0,
            ]);

            // Credit Revenue
            $entry->lines()->create([
                'chart_of_account_id' => $revenueAccount->id,
                'debit' => 0,
                'credit' => $invoice->grand_total,
            ]);

            return $entry;
        });
    }

    /**
     * Post a payment receipt to the general ledger.
     * Debit: Cash/Bank (Asset)
     * Credit: Accounts Receivable (Asset)
     */
    public function postPayment(PaymentReceipt $receipt): JournalEntry
    {
        return DB::transaction(function () use ($receipt) {
            $this->ensurePeriodIsOpen($receipt->tenant_id, $receipt->created_at->toDateString());

            $cashAccount = $this->getOrCreateAccount($receipt->tenant_id, '1000', 'Cash and Cash Equivalents', 'asset');
            $arAccount = $this->getOrCreateAccount($receipt->tenant_id, '1200', 'Accounts Receivable', 'asset');

            $entry = JournalEntry::create([
                'tenant_id' => $receipt->tenant_id,
                'entry_date' => $receipt->created_at->toDateString(),
                'reference_number' => $receipt->receipt_number,
                'source_type' => PaymentReceipt::class,
                'source_id' => $receipt->id,
                'description' => "Payment received for {$receipt->receipt_number}",
            ]);

            // Debit Cash
            $entry->lines()->create([
                'chart_of_account_id' => $cashAccount->id,
                'debit' => $receipt->amount,
                'credit' => 0,
            ]);

            // Credit AR
            $entry->lines()->create([
                'chart_of_account_id' => $arAccount->id,
                'debit' => 0,
                'credit' => $receipt->amount,
            ]);

            return $entry;
        });
    }

    /**
     * Ensure the financial period is open for a given date.
     */
    protected function ensurePeriodIsOpen(int $tenantId, $date): void
    {
        $dateStr = $date instanceof \Carbon\Carbon ? $date->toDateString() : (string)$date;
        $period = FinancialPeriod::where('tenant_id', $tenantId)
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->first();

        if ($period && $period->status === 'closed') {
            throw new Exception("Cannot post to a closed financial period ({$period->period_name}).");
        }
    }

    /**
     * Automatically seeds the basic chart of accounts if they don't exist.
     */
    protected function getOrCreateAccount(int $tenantId, string $code, string $name, string $type): ChartOfAccount
    {
        return ChartOfAccount::firstOrCreate(
            ['tenant_id' => $tenantId, 'account_code' => $code],
            ['account_name' => $name, 'account_type' => $type, 'is_active' => true]
        );
    }
}
