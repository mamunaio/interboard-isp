<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Models\PaymentAllocation;
use App\Modules\Billing\Models\PaymentAllocationReversal;
use App\Modules\Billing\Models\ReversalEvent;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Billing\Enums\TransactionType;
use App\Modules\Billing\Enums\TransactionDirection;
use App\Modules\Customer\Models\Customer;
use App\Modules\Auth\Models\StaffUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;

class ReversalAdjustmentService
{
    public function __construct(
        protected WalletService $walletService,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Reverse a posted payment receipt without mutating historical models.
     */
    public function reverseReceipt(
        int $tenantId,
        int $receiptId,
        string $reason,
        StaffUser $actor,
        array $options = []
    ): ReversalEvent {
        // 1. Strict RBAC Enforcement
        $this->authorize($actor, 'billing.receipt.reverse');

        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reason' => ['An explicit reason is required to perform a receipt reversal.']
            ]);
        }

        return DB::transaction(function () use ($tenantId, $receiptId, $reason, $actor, $options) {
            // 2. Lock receipt
            $receipt = PaymentReceipt::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($receiptId);

            // 3. Prevent double reversal by checking ReversalEvent existence
            $alreadyReversed = ReversalEvent::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('original_entity_type', PaymentReceipt::class)
                ->where('original_entity_id', $receipt->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyReversed) {
                throw ValidationException::withMessages([
                    'receipt' => ['This payment receipt has already been reversed.']
                ]);
            }

            // 4. Lock customer wallet
            $wallet = Wallet::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('owner_type', Customer::class)
                ->where('owner_id', $receipt->customer_id)
                ->where('currency', $receipt->currency)
                ->lockForUpdate()
                ->firstOrFail();

            // 5. Unwind allocations specifically linked to this receipt's explicit relational provenance
            $allocations = PaymentAllocation::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('wallet_id', $wallet->id)
                ->where('payment_receipt_id', $receipt->id)
                ->lockForUpdate()
                ->get();

            foreach ($allocations as $allocation) {
                // Ensure allocation hasn't already been reversed
                $allocAlreadyReversed = PaymentAllocationReversal::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('payment_allocation_id', $allocation->id)
                    ->lockForUpdate()
                    ->exists();

                if ($allocAlreadyReversed) {
                    continue;
                }

                $allocAmount = (string)$allocation->allocation_amount;

                // Create immutable allocation reversal record
                PaymentAllocationReversal::create([
                    'tenant_id' => $tenantId,
                    'payment_allocation_id' => $allocation->id,
                    'amount' => $allocAmount,
                    'reason' => "Receipt {$receipt->receipt_number} reversed.",
                ]);

                // Issue compensating MANUAL_ADJUSTMENT Invoice representing the restored debt
                $chargeInvoice = $this->invoiceService->createInvoice($tenantId, $receipt->customer_id, [
                    'invoice_type' => InvoiceType::MANUAL_ADJUSTMENT->value,
                    'issue_date' => now()->toDateString(),
                    'due_date' => now()->addDays(7)->toDateString(),
                    'notes' => "Compensating invoice for reversed allocation on Receipt {$receipt->receipt_number}.",
                ], [
                    [
                        'line_type' => 'service',
                        'description' => "Reversed allocation chargeback for Invoice #{$allocation->invoice_id}",
                        'quantity' => '1.0000',
                        'unit_price' => $allocAmount,
                    ]
                ]);
                $this->invoiceService->transitionInvoiceStatus($chargeInvoice, InvoiceStatus::ISSUED);

                // Post compensating Wallet CREDIT to prevent double-charging (since the new invoice represents the debt)
                $this->walletService->mutateBalance(
                    $tenantId,
                    $wallet->id,
                    TransactionDirection::CREDIT->value,
                    $allocAmount,
                    TransactionType::PAYMENT_ALLOCATION_REVERSAL->value,
                    [
                        'notes' => "Compensating credit for reversed allocation of Receipt {$receipt->receipt_number}.",
                        'created_by_type' => 'StaffUser',
                        'created_by_id' => $actor->id,
                    ]
                );
            }

            // 6. Post main receipt DEBIT to deduct original amount from wallet
            $amountToDebit = (string)$receipt->amount;
            $ledgerEntry = $this->walletService->mutateBalance(
                $tenantId,
                $wallet->id,
                TransactionDirection::DEBIT->value,
                $amountToDebit,
                TransactionType::PAYMENT_RECEIPT_REVERSAL->value,
                [
                    'notes' => "Reversal of receipt {$receipt->receipt_number}. Reason: {$reason}",
                    'created_by_type' => 'StaffUser',
                    'created_by_id' => $actor->id,
                ]
            );

            // 7. Write Immutable ReversalEvent Audit entry
            return ReversalEvent::create([
                'tenant_id' => $tenantId,
                'original_entity_type' => PaymentReceipt::class,
                'original_entity_id' => $receipt->id,
                'reversal_type' => TransactionType::PAYMENT_RECEIPT_REVERSAL->value,
                'amount' => $amountToDebit,
                'currency' => $receipt->currency,
                'reason' => $reason,
                'reversal_ledger_entry_id' => $ledgerEntry->id,
                'idempotency_key' => $options['idempotency_key'] ?? null,
                'created_by_type' => 'StaffUser',
                'created_by_id' => $actor->id,
            ]);
        });
    }

    /**
     * Perform a manual financial adjustment (credit, debit, waiver, penalty) with idempotency protection.
     */
    public function adjustBalance(
        int $tenantId,
        int $customerId,
        string $type,
        string $amount,
        string $currency,
        string $reason,
        StaffUser $actor,
        array $options = []
    ): ReversalEvent {
        // 1. Strict RBAC Enforcement
        $this->authorize($actor, 'billing.adjustment.manage');

        $typeEnum = TransactionType::tryFrom($type);
        if (!$typeEnum || !in_array($typeEnum, [
            TransactionType::CREDIT_ADJUSTMENT,
            TransactionType::DEBIT_ADJUSTMENT,
            TransactionType::WAIVER,
            TransactionType::PENALTY
        ])) {
            throw ValidationException::withMessages([
                'type' => ['Invalid manual adjustment type provided.']
            ]);
        }

        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reason' => ['An explicit reason is required to perform a manual financial adjustment.']
            ]);
        }

        $amountStr = bcadd($amount, '0', 4);
        if (bccomp($amountStr, '0.0000', 4) <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Adjustment amount must be strictly greater than 0.0000.']
            ]);
        }

        return DB::transaction(function () use ($tenantId, $customerId, $typeEnum, $amountStr, $currency, $reason, $actor, $options) {
            // 2. Lock-Safe Idempotency Check
            if (!empty($options['idempotency_key'])) {
                $existing = ReversalEvent::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('idempotency_key', $options['idempotency_key'])
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $customer = Customer::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($customerId);

            $wallet = Wallet::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('owner_type', Customer::class)
                ->where('owner_id', $customer->id)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            $direction = in_array($typeEnum, [TransactionType::CREDIT_ADJUSTMENT, TransactionType::WAIVER])
                ? TransactionDirection::CREDIT->value
                : TransactionDirection::DEBIT->value;

            // Mutate wallet balance
            $ledgerEntry = $this->walletService->mutateBalance(
                $tenantId,
                $wallet->id,
                $direction,
                $amountStr,
                $typeEnum->value,
                [
                    'notes' => "Manual adjustment: {$reason}",
                    'created_by_type' => 'StaffUser',
                    'created_by_id' => $actor->id,
                ]
            );

            // Log adjustment audit row in reversal_events
            return ReversalEvent::create([
                'tenant_id' => $tenantId,
                'original_entity_type' => Wallet::class,
                'original_entity_id' => $wallet->id,
                'reversal_type' => $typeEnum->value,
                'amount' => $amountStr,
                'currency' => $currency,
                'reason' => $reason,
                'reversal_ledger_entry_id' => $ledgerEntry->id,
                'idempotency_key' => $options['idempotency_key'] ?? null,
                'created_by_type' => 'StaffUser',
                'created_by_id' => $actor->id,
            ]);
        });
    }

    /**
     * Enforce staff permission policy.
     */
    protected function authorize(StaffUser $user, string $permissionSlug): void
    {
        $hasPermission = $user->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })->exists();

        if (!$hasPermission) {
            throw new AuthorizationException("Unauthorized: Missing required permission [{$permissionSlug}].");
        }
    }
}
