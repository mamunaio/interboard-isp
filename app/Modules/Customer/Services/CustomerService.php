<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\CustomerStatus;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    /**
     * Create a new tenant-scoped customer record safely.
     */
    public function createCustomer(int $tenantId, array $data): Customer
    {
        // 1. Generate normalized searchable blind hashes for encrypted PII fields
        $data = $this->populateSearchableHashes($data);

        // 2. Perform custom validations (tenant-scoped uniqueness checks)
        $this->performUniquenessChecks($tenantId, $data);

        // 3. Concurrency-safe customer code generation (No count-based checks!)
        $data['customer_code'] = $this->generateCustomerCode($tenantId);

        // 4. Force tenant ownership context
        $data['tenant_id'] = $tenantId;

        // 5. Force initial lifecycle status if not provided
        if (!isset($data['status'])) {
            $data['status'] = CustomerStatus::LEAD;
        }

        return Customer::create($data);
    }

    /**
     * Update an existing customer record safely.
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        $tenantId = (int)$customer->tenant_id;

        // 1. Generate normalized searchable blind hashes for encrypted PII fields
        $data = $this->populateSearchableHashes($data);

        // 2. Perform custom validations (excluding current customer record)
        $this->performUniquenessChecks($tenantId, $data, (int)$customer->id);

        // 3. If status change is requested, enforce lifecycle transitions
        if (isset($data['status'])) {
            $newStatus = $data['status'] instanceof CustomerStatus 
                ? $data['status'] 
                : CustomerStatus::from($data['status']);

            $this->validateTransition($customer, $newStatus);
        }

        $customer->update($data);

        return $customer;
    }

    /**
     * Transition a customer's lifecycle status securely.
     */
    public function transitionStatus(Customer $customer, CustomerStatus $newStatus): Customer
    {
        $this->validateTransition($customer, $newStatus);

        $customer->update(['status' => $newStatus]);

        return $customer;
    }

    /**
     * Enforce customer status transition rules.
     */
    protected function validateTransition(Customer $customer, CustomerStatus $newStatus): void
    {
        $currentStatus = $customer->status;

        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => ["Invalid status transition: Cannot transition from '{$currentStatus->value}' to '{$newStatus->value}'."]
            ]);
        }
    }

    /**
     * Generate normalized blind hashes for encrypted PII inputs to allow high-performance unique indexes and searches.
     */
    protected function populateSearchableHashes(array $data): array
    {
        if (isset($data['national_id'])) {
            $data['national_id_hash'] = !empty($data['national_id'])
                ? hash('sha256', Str::lower(trim((string)$data['national_id'])))
                : null;
        }

        if (isset($data['business_registration'])) {
            $data['business_registration_hash'] = !empty($data['business_registration'])
                ? hash('sha256', Str::lower(trim((string)$data['business_registration'])))
                : null;
        }

        return $data;
    }

    /**
     * Perform strict tenant-scoped uniqueness validations.
     * Uses SHA-256 blind indexing for encrypted fields to run high-performance SQL index scans.
     */
    protected function performUniquenessChecks(int $tenantId, array $data, ?int $excludeCustomerId = null): void
    {
        // 1. Validate Email Uniqueness (Application validation for nullable field)
        if (!empty($data['email'])) {
            $emailQuery = Customer::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('email', trim($data['email']));

            if ($excludeCustomerId !== null) {
                $emailQuery->where('id', '!=', $excludeCustomerId);
            }

            if ($emailQuery->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['The Email must be unique within this tenant context.']
                ]);
            }
        }

        // 2. Validate National ID Uniqueness (Using blind index hash to support encrypted SQL indexing!)
        if (!empty($data['national_id_hash'])) {
            $nidQuery = Customer::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('national_id_hash', $data['national_id_hash']);

            if ($excludeCustomerId !== null) {
                $nidQuery->where('id', '!=', $excludeCustomerId);
            }

            if ($nidQuery->exists()) {
                throw ValidationException::withMessages([
                    'national_id' => ['The National ID must be unique within this tenant context.']
                ]);
            }
        }

        // 3. Validate Business Registration Uniqueness (Using blind index hash to support encrypted SQL indexing!)
        if (!empty($data['business_registration_hash'])) {
            $brQuery = Customer::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('business_registration_hash', $data['business_registration_hash']);

            if ($excludeCustomerId !== null) {
                $brQuery->where('id', '!=', $excludeCustomerId);
            }

            if ($brQuery->exists()) {
                throw ValidationException::withMessages([
                    'business_registration' => ['The Business Registration must be unique within this tenant context.']
                ]);
            }
        }
    }

    /**
     * Generate a concurrency-safe customer code scoped per tenant using Redis sequence counters.
     * Falls back to a deterministic, transaction-safe database lock sequence on Redis failure.
     */
    public function generateCustomerCode(int $tenantId): string
    {
        $year = date('Y');
        $redisKey = "tenant:{$tenantId}:customer_code_seq:{$year}";

        try {
            // Redis INCR is fully atomic and concurrency-safe
            $sequence = Redis::incr($redisKey);
        } catch (\Throwable $e) {
            // Deterministic collision-safe fallback: Pessimistic transaction-locked sequence table!
            $sequence = DB::transaction(function () use ($tenantId, $year) {
                $seq = DB::table('customer_code_sequences')
                    ->where('tenant_id', $tenantId)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if (!$seq) {
                    DB::table('customer_code_sequences')->insert([
                        'tenant_id' => $tenantId,
                        'year' => $year,
                        'last_value' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    return 1;
                }

                $newValue = (int)$seq->last_value + 1;
                
                DB::table('customer_code_sequences')
                    ->where('tenant_id', $tenantId)
                    ->where('year', $year)
                    ->update([
                        'last_value' => $newValue,
                        'updated_at' => now(),
                    ]);

                return $newValue;
            });
        }

        $seqFormatted = str_pad((string)$sequence, 5, '0', STR_PAD_LEFT);
        
        return "CUST-{$year}-{$seqFormatted}";
    }
}
