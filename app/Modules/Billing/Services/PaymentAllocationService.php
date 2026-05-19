<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\PaymentAllocation;
use App\Modules\Billing\Models\LedgerEntry;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\TransactionType;
use App\Modules\Billing\Enums\TransactionDirection;
use App\Modules\Customer\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

class PaymentAllocationService
{
    /**
     * Allocate payment from a Customer's Wallet to outstanding eligible invoices in strict FIFO order.
     */
    public function allocateFromWallet(int $tenantId, int $walletId, string $allocationAmount, array $options = []): Collection
    {
        // 1. Validate option metadata if present
        if (isset($options['metadata']) && is_array($options['metadata']) && !empty($options['metadata'])) {
            if (array_keys($options['metadata']) === range(0, count($options['metadata']) - 1)) {
                throw ValidationException::withMessages([
                    'metadata' => ['Metadata must be a valid associative JSON object.']
                ]);
            }
        }

        // 2. Perform safe, atomic mutation order
        return DB::transaction(function () use ($tenantId, $walletId, $allocationAmount, $options) {
            // A. lock wallet row
            $wallet = Wallet::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($walletId);

            // B. Check strict idempotency (tenant + wallet + key)
            if (!empty($options['idempotency_key'])) {
                $existing = PaymentAllocation::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('wallet_id', $walletId)
                    ->where('idempotency_key', $options['idempotency_key'])
                    ->orderBy('allocation_order', 'asc')
                    ->get();

                if ($existing->isNotEmpty()) {
                    return $existing;
                }
            }

            // C. Validate active state of wallet
            if (!$wallet->active) {
                throw ValidationException::withMessages([
                    'wallet' => ['The wallet is inactive and cannot perform allocations.']
                ]);
            }

            // D. Validate allocation owner is customer
            if ($wallet->owner_type !== Customer::class) {
                throw ValidationException::withMessages([
                    'owner_type' => ['Only customer wallets are currently eligible for payment allocations.']
                ]);
            }

            // E. Validate allocation amount > 0
            $allocationAmountStr = bcadd($allocationAmount, '0', 4);
            if (bccomp($allocationAmountStr, '0.0000', 4) <= 0) {
                throw ValidationException::withMessages([
                    'allocation_amount' => ['Allocation amount must be strictly greater than 0.0000.']
                ]);
            }

            // F. Validate sufficient wallet balance for the requested allocation
            if (bccomp($wallet->current_balance, $allocationAmountStr, 4) === -1) {
                throw ValidationException::withMessages([
                    'balance' => ['Insufficient wallet balance.']
                ]);
            }

            // G. Query and lock eligible outstanding invoices for the wallet's owner
            $invoices = Invoice::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $wallet->owner_id)
                ->whereIn('status', [InvoiceStatus::ISSUED->value, InvoiceStatus::PARTIALLY_PAID->value])
                ->lockForUpdate()
                ->get();

            if ($invoices->isEmpty()) {
                throw ValidationException::withMessages([
                    'invoices' => ['No outstanding eligible invoices found for allocation.']
                ]);
            }

            // H. Sort invoices strictly in FIFO chronological order with deterministic tie-breaker
            $sortedInvoices = $invoices->sortBy(function ($invoice) {
                return [
                    $invoice->due_date->format('Y-m-d'),
                    $invoice->invoice_number
                ];
            });

            $remainingToAllocate = $allocationAmountStr;
            $allocationsToCreate = [];
            $allocationOrder = 1;
            $totalActualAllocated = '0.0000';

            // I. Loop FIFO and allocate
            foreach ($sortedInvoices as $invoice) {
                if (bccomp($remainingToAllocate, '0.0000', 4) <= 0) {
                    break;
                }

                // Outstanding amount is grand_total - amount_paid
                $outstandingAmount = bcsub($invoice->grand_total, $invoice->amount_paid ?? '0.0000', 4);
                if (bccomp($outstandingAmount, '0.0000', 4) <= 0) {
                    continue;
                }

                // Determine allocate for this invoice
                if (bccomp($remainingToAllocate, $outstandingAmount, 4) === 1) { // remaining > outstanding
                    $allocateForThisInvoice = $outstandingAmount;
                } else {
                    $allocateForThisInvoice = $remainingToAllocate;
                }

                $remainingToAllocate = bcsub($remainingToAllocate, $allocateForThisInvoice, 4);
                $totalActualAllocated = bcadd($totalActualAllocated, $allocateForThisInvoice, 4);

                $newAmountPaid = bcadd($invoice->amount_paid ?? '0.0000', $allocateForThisInvoice, 4);
                $newAmountDue = bcsub($invoice->grand_total, $newAmountPaid, 4);

                if (bccomp($newAmountDue, '0.0000', 4) === -1) {
                    $newAmountDue = '0.0000';
                }

                $newStatus = bccomp($newAmountDue, '0.0000', 4) === 0 ? InvoiceStatus::PAID : InvoiceStatus::PARTIALLY_PAID;

                // Update invoice
                $invoice->amount_paid = $newAmountPaid;
                $invoice->amount_due = $newAmountDue;
                $invoice->status = $newStatus;
                $invoice->save();

                $allocationsToCreate[] = [
                    'invoice_id' => $invoice->id,
                    'allocation_amount' => $allocateForThisInvoice,
                    'allocation_order' => $allocationOrder++,
                ];
            }

            // J. Verify if any actual allocation was performed
            if (bccomp($totalActualAllocated, '0.0000', 4) === 0) {
                throw ValidationException::withMessages([
                    'invoices' => ['No outstanding balances exist on eligible invoices to allocate.']
                ]);
            }

            // K. Create immutable wallet debit ledger entry for the total actual allocated amount
            $balanceBefore = $wallet->current_balance;
            $balanceAfter = bcsub($balanceBefore, $totalActualAllocated, 4);

            $ledger = LedgerEntry::create([
                'tenant_id' => $tenantId,
                'wallet_id' => $wallet->id,
                'transaction_type' => TransactionType::PAYMENT_ALLOCATION->value,
                'direction' => TransactionDirection::DEBIT->value,
                'amount' => $totalActualAllocated,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => null,
                'reference_id' => null,
                'idempotency_key' => $options['idempotency_key'] ?? null,
                'notes' => $options['notes'] ?? 'Wallet debit for payment allocations.',
                'metadata' => $options['metadata'] ?? null,
                'created_by_type' => $options['created_by_type'] ?? null,
                'created_by_id' => $options['created_by_id'] ?? null,
            ]);

            // L. Update wallet current_balance
            $wallet->current_balance = $balanceAfter;
            $wallet->save();

            // M. Insert immutable payment allocation rows
            $allocations = collect();
            foreach ($allocationsToCreate as $item) {
                $alloc = PaymentAllocation::create([
                    'tenant_id' => $tenantId,
                    'invoice_id' => $item['invoice_id'],
                    'wallet_id' => $wallet->id,
                    'ledger_entry_id' => $ledger->id,
                    'allocation_amount' => $item['allocation_amount'],
                    'allocation_order' => $item['allocation_order'],
                    'idempotency_key' => $options['idempotency_key'] ?? null,
                    'notes' => $options['notes'] ?? null,
                    'metadata' => $options['metadata'] ?? null,
                ]);
                $allocations->push($alloc);
            }

            return $allocations;
        });
    }
}
