<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Enums\ReceiptStatus;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\TransactionType;
use App\Modules\Billing\Enums\TransactionDirection;
use App\Modules\Customer\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PaymentReceiptService
{
    public function __construct(
        protected WalletService $walletService,
        protected PaymentAllocationService $allocationService
    ) {}

    /**
     * Ingest a manual payment receipt under strict concurrent safety rules.
     */
    public function createReceipt(int $tenantId, array $data): PaymentReceipt
    {
        // 1. Basic input validation
        $customerId = (int)($data['customer_id'] ?? 0);
        $amount = (string)($data['amount'] ?? '0.0000');
        $currency = strtoupper(trim($data['currency'] ?? 'BDT'));
        $method = $data['payment_method'] ?? null;
        $reference = isset($data['reference']) ? trim((string)$data['reference']) : null;

        if ($method instanceof PaymentMethod) {
            $methodEnum = $method;
        } else {
            $methodEnum = PaymentMethod::tryFrom((string)$method);
        }

        if (!$methodEnum) {
            throw ValidationException::withMessages([
                'payment_method' => ['Invalid payment method provided.']
            ]);
        }

        // Enforce positive decimal amount
        $amountStr = bcadd($amount, '0', 4);
        if (bccomp($amountStr, '0.0000', 4) <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Receipt amount must be strictly greater than 0.0000.']
            ]);
        }

        return DB::transaction(function () use ($tenantId, $customerId, $amountStr, $currency, $methodEnum, $reference, $data) {
            // 2. Lock and validate customer context
            $customer = Customer::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find($customerId);

            if (!$customer) {
                throw ValidationException::withMessages([
                    'customer_id' => ['Customer not found or tenant mismatch.']
                ]);
            }

            // 3. Find Customer Wallet (Do NOT auto-create; throw if missing)
            $wallet = Wallet::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('owner_type', Customer::class)
                ->where('owner_id', $customer->id)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                throw ValidationException::withMessages([
                    'currency' => ["No active wallet initialized for currency {$currency} on this customer."]
                ]);
            }

            // 4. Idempotency Check with lock
            if ($reference !== null && $reference !== '') {
                $exists = PaymentReceipt::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('payment_method', $methodEnum->value)
                    ->where('reference', $reference)
                    ->lockForUpdate()
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'reference' => ['A receipt with the same reference and payment method already exists for this tenant.']
                    ]);
                }
            }

            // 5. Generate Race-Safe deterministic Receipt Number sequence
            $receiptNumber = $this->generateReceiptNumber($tenantId);

            // 6. Credit Wallet via WalletService first (yielding the ledger entry ID)
            $ledgerEntry = $this->walletService->mutateBalance(
                $tenantId,
                $wallet->id,
                TransactionDirection::CREDIT->value,
                $amountStr,
                TransactionType::MANUAL_PAYMENT_RECEIPT->value,
                [
                    'notes' => "Payment received via {$methodEnum->value}.",
                    'created_by_type' => $data['created_by_type'] ?? null,
                    'created_by_id' => $data['created_by_id'] ?? null,
                ]
            );

            // 7. Create immutable receipt document in a SINGLE insert (no subsequent updates)
            $receipt = PaymentReceipt::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'receipt_number' => $receiptNumber,
                'payment_method' => $methodEnum->value,
                'amount' => $amountStr,
                'currency' => $currency,
                'reference' => $reference,
                'notes' => $data['notes'] ?? null,
                'status' => ReceiptStatus::POSTED->value,
                'wallet_ledger_entry_id' => $ledgerEntry->id,
                'created_by_type' => $data['created_by_type'] ?? null,
                'created_by_id' => $data['created_by_id'] ?? null,
            ]);

            // 8. Deterministic FIFO auto-allocation (if enabled and outstanding invoices exist)
            $autoAllocate = (bool)($data['auto_allocate'] ?? false);
            if ($autoAllocate) {
                $hasOutstanding = Invoice::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('customer_id', $customer->id)
                    ->whereIn('status', [InvoiceStatus::ISSUED->value, InvoiceStatus::PARTIALLY_PAID->value])
                    ->exists();

                if ($hasOutstanding) {
                    $this->allocationService->allocateFromWallet(
                        $tenantId,
                        $wallet->id,
                        $amountStr,
                        [
                            'notes' => "Automatic allocation for receipt {$receipt->receipt_number}.",
                            'metadata' => [
                                'payment_receipt_id' => $receipt->id,
                            ],
                            'created_by_type' => $data['created_by_type'] ?? null,
                            'created_by_id' => $data['created_by_id'] ?? null,
                        ]
                    );
                }
            }

            return $receipt;
        });
    }

    /**
     * Deterministic race-safe sequence generator.
     */
    protected function generateReceiptNumber(int $tenantId): string
    {
        $year = date('Y');

        // Safe upsert to ensure the row exists before locking
        try {
            DB::table('receipt_number_sequences')->insert([
                'tenant_id' => $tenantId,
                'year' => $year,
                'last_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Row already exists or concurrent request created it, safely ignore
        }

        // Lock and increment
        $sequence = DB::table('receipt_number_sequences')
            ->where('tenant_id', $tenantId)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        $newValue = (int)$sequence->last_value + 1;
        DB::table('receipt_number_sequences')
            ->where('tenant_id', $tenantId)
            ->where('year', $year)
            ->update([
                'last_value' => $newValue,
                'updated_at' => now(),
            ]);

        $seqFormatted = str_pad((string)$newValue, 6, '0', STR_PAD_LEFT);
        return "RCPT-{$year}-{$seqFormatted}";
    }
}
