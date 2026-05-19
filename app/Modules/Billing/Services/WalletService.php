<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\LedgerEntry;
use App\Modules\Billing\Enums\TransactionType;
use App\Modules\Billing\Enums\TransactionDirection;
use App\Modules\Customer\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    /**
     * Create a wallet for a morphable owner per currency and tenant.
     */
    public function createWallet(int $tenantId, string $ownerType, int $ownerId, string $currency, array $metadata = []): Wallet
    {
        // 1. Validate owner class
        if ($ownerType !== Customer::class) {
            throw ValidationException::withMessages([
                'owner_type' => ['Only App\Modules\Customer\Models\Customer owner is currently supported.']
            ]);
        }

        // 2. Validate metadata is associative JSON
        if (!empty($metadata) && (array_keys($metadata) === range(0, count($metadata) - 1))) {
            throw ValidationException::withMessages([
                'metadata' => ['Metadata must be a valid associative JSON object.']
            ]);
        }

        // 3. Validate owner exists and belongs to the same tenant
        $owner = $ownerType::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($ownerId);

        if (!$owner) {
            throw ValidationException::withMessages([
                'owner' => ['The specified owner record does not exist or does not belong to this tenant.']
            ]);
        }

        // 4. Validate duplicate currency wallet per owner
        $normalizedCurrency = strtoupper(trim($currency));
        if (strlen($normalizedCurrency) !== 3) {
            throw ValidationException::withMessages([
                'currency' => ['Currency must be a valid 3-character ISO code.']
            ]);
        }

        $exists = Wallet::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('currency', $normalizedCurrency)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'wallet' => ['A wallet already exists for this owner and currency under the specified tenant.']
            ]);
        }

        return Wallet::create([
            'tenant_id' => $tenantId,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'currency' => $normalizedCurrency,
            'current_balance' => '0.0000',
            'active' => true,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mutate wallet balance in a race-condition-free, idempotent transaction.
     */
    public function mutateBalance(int $tenantId, int $walletId, string $direction, string $amount, string $transactionType, array $options = []): LedgerEntry
    {
        // 1. Validate metadata option is associative JSON
        if (isset($options['metadata']) && is_array($options['metadata']) && !empty($options['metadata'])) {
            if (array_keys($options['metadata']) === range(0, count($options['metadata']) - 1)) {
                throw ValidationException::withMessages([
                    'metadata' => ['Metadata must be a valid associative JSON object.']
                ]);
            }
        }

        // 2. Perform safe, atomic mutation order
        return DB::transaction(function () use ($tenantId, $walletId, $direction, $amount, $transactionType, $options) {
            // A. lock wallet row
            $wallet = Wallet::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($walletId);

            // B. Check idempotency (now safely executed after acquiring lock)
            if (!empty($options['idempotency_key'])) {
                $existing = LedgerEntry::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('wallet_id', $walletId)
                    ->where('idempotency_key', $options['idempotency_key'])
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            // C. check active state
            if (!$wallet->active) {
                throw ValidationException::withMessages([
                    'wallet' => ['The wallet is inactive and cannot perform transactions.']
                ]);
            }

            // D. validate transaction_type & direction enums
            $typeEnum = TransactionType::tryFrom($transactionType);
            if (!$typeEnum) {
                throw ValidationException::withMessages([
                    'transaction_type' => ['Invalid transaction type.']
                ]);
            }

            $directionEnum = TransactionDirection::tryFrom($direction);
            if (!$directionEnum) {
                throw ValidationException::withMessages([
                    'direction' => ['Invalid direction.']
                ]);
            }

            // E. validate positive amount
            $amountStr = bcadd($amount, '0', 4);
            if (bccomp($amountStr, '0.0000', 4) <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['Transaction amount must be strictly greater than 0.0000.']
                ]);
            }

            // F. calculate balances
            $balanceBefore = bcadd($wallet->current_balance ?? '0.0000', '0', 4);
            if ($directionEnum === TransactionDirection::CREDIT) {
                $balanceAfter = bcadd($balanceBefore, $amountStr, 4);
            } else {
                $balanceAfter = bcsub($balanceBefore, $amountStr, 4);

                // G. validate balance sufficiency
                if (bccomp($balanceAfter, '0.0000', 4) === -1) {
                    throw ValidationException::withMessages([
                        'balance' => ['Insufficient wallet balance.']
                    ]);
                }
            }

            // H. insert immutable ledger entry
            $ledger = LedgerEntry::create([
                'tenant_id' => $tenantId,
                'wallet_id' => $wallet->id,
                'transaction_type' => $typeEnum->value,
                'direction' => $directionEnum->value,
                'amount' => $amountStr,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $options['reference_type'] ?? null,
                'reference_id' => $options['reference_id'] ?? null,
                'idempotency_key' => $options['idempotency_key'] ?? null,
                'notes' => $options['notes'] ?? null,
                'metadata' => $options['metadata'] ?? null,
                'created_by_type' => $options['created_by_type'] ?? null,
                'created_by_id' => $options['created_by_id'] ?? null,
            ]);

            // I. update wallet current_balance
            $wallet->current_balance = $balanceAfter;
            $wallet->save();

            return $ledger;
        });
    }
}
