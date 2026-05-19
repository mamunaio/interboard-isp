<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerContract;
use App\Modules\Customer\Models\ServicePackage;
use App\Modules\Customer\Enums\ContractStatus;
use App\Modules\Customer\Enums\ServiceType;
use App\Modules\Customer\Enums\CustomerType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;

class ContractPackageService
{
    /**
     * Create a new Customer Contract safely.
     */
    public function createContract(int $tenantId, int $customerId, array $data): CustomerContract
    {
        // 1. Verify tenant-scoped Customer ownership strictly
        $customer = $this->resolveAndValidateCustomer($tenantId, $customerId);

        // 2. Schema validate terms_snapshot
        if (isset($data['terms_snapshot'])) {
            $data['terms_snapshot'] = $this->validateTermsSnapshot($data['terms_snapshot']);
        }

        // 3. Generate deterministic and collision-safe contract number (Database pessimistic locked sequence)
        $data['contract_number'] = $this->generateContractNumber($tenantId);

        // 4. Force tenant ownership
        $data['tenant_id'] = $customer->tenant_id;
        $data['customer_id'] = $customer->id;

        // 5. Enforce contract activation rule consistency
        $status = isset($data['status'])
            ? ($data['status'] instanceof ContractStatus ? $data['status'] : ContractStatus::from($data['status']))
            : ContractStatus::DRAFT;

        if ($status === ContractStatus::ACTIVE && empty($data['signed_at'])) {
            throw ValidationException::withMessages([
                'signed_at' => ['A contract cannot be activated without a signed date.']
            ]);
        }

        $data['status'] = $status;

        return CustomerContract::create($data);
    }

    /**
     * Transition a contract's lifecycle status securely.
     */
    public function transitionContractStatus(CustomerContract $contract, ContractStatus $newStatus, array $extraData = []): CustomerContract
    {
        $currentStatus = $contract->status;

        // 1. Enforce explicit transition rules
        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => ["Invalid status transition: Cannot transition from '{$currentStatus->value}' to '{$newStatus->value}'."]
            ]);
        }

        $updateData = ['status' => $newStatus];

        // 2. Enforce activation rules consistency
        if ($newStatus === ContractStatus::ACTIVE) {
            $signedAt = $extraData['signed_at'] ?? $contract->signed_at;
            if (empty($signedAt)) {
                throw ValidationException::withMessages([
                    'signed_at' => ['A contract cannot be activated without a signed date.']
                ]);
            }
            $updateData['signed_at'] = $signedAt;
        }

        $contract->update($updateData);

        return $contract;
    }

    /**
     * Create a new Service Package safely.
     */
    public function createServicePackage(int $tenantId, array $data): ServicePackage
    {
        // 1. Unique package_code per tenant validation
        $this->validatePackageCodeUniqueness($tenantId, $data['package_code']);

        // 2. Validate package configuration & speeds logic
        $data = $this->validateAndNormalizePackage($data);

        // 3. Force tenant ID
        $data['tenant_id'] = $tenantId;

        return ServicePackage::create($data);
    }

    /**
     * Update an existing Service Package safely.
     */
    public function updateServicePackage(ServicePackage $package, array $data): ServicePackage
    {
        $tenantId = (int)$package->tenant_id;

        // 1. Unique package_code per tenant validation (excluding current package)
        if (isset($data['package_code'])) {
            $this->validatePackageCodeUniqueness($tenantId, $data['package_code'], (int)$package->id);
        }

        // 2. Validate package configuration & speeds logic
        $data = $this->validateAndNormalizePackage($data, $package->toArray());

        $package->update($data);

        return $package;
    }

    /**
     * Retrieve and validate that the customer belongs to the active tenant strictly.
     */
    protected function resolveAndValidateCustomer(int $tenantId, int $customerId): Customer
    {
        $customer = Customer::withoutGlobalScopes()
            ->where('id', $customerId)
            ->first();

        if (!$customer || (int)$customer->tenant_id !== $tenantId) {
            throw ValidationException::withMessages([
                'customer_id' => ['The selected customer does not belong to the active tenant context.']
            ]);
        }

        return $customer;
    }

    /**
     * Generate deterministic, collision-safe contract numbers using database transaction-locked sequences.
     */
    public function generateContractNumber(int $tenantId): string
    {
        $year = date('Y');

        $sequence = DB::transaction(function () use ($tenantId, $year) {
            $seq = DB::table('contract_number_sequences')
                ->where('tenant_id', $tenantId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                DB::table('contract_number_sequences')->insert([
                    'tenant_id' => $tenantId,
                    'year' => $year,
                    'last_value' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return 1;
            }

            $newValue = (int)$seq->last_value + 1;
            
            DB::table('contract_number_sequences')
                ->where('tenant_id', $tenantId)
                ->where('year', $year)
                ->update([
                    'last_value' => $newValue,
                    'updated_at' => now(),
                ]);

            return $newValue;
        });

        $seqFormatted = str_pad((string)$sequence, 5, '0', STR_PAD_LEFT);
        
        return "CON-{$year}-{$seqFormatted}";
    }

    /**
     * Schema validate terms_snapshot.
     * Required keys: sla_level (string), billing_cycle (string), minimum_commitment_months (integer)
     */
    protected function validateTermsSnapshot(array $snapshot): array
    {
        $requiredKeys = ['sla_level', 'billing_cycle', 'minimum_commitment_months'];
        $cleanSnapshot = [];

        foreach ($requiredKeys as $key) {
            if (!isset($snapshot[$key])) {
                throw ValidationException::withMessages([
                    'terms_snapshot' => ["Missing required terms snapshot schema field: '{$key}'."]
                ]);
            }
        }

        $cleanSnapshot['sla_level'] = trim((string)$snapshot['sla_level']);
        $cleanSnapshot['billing_cycle'] = trim((string)$snapshot['billing_cycle']);
        $cleanSnapshot['minimum_commitment_months'] = (int)$snapshot['minimum_commitment_months'];

        if ($cleanSnapshot['minimum_commitment_months'] < 0) {
            throw ValidationException::withMessages([
                'terms_snapshot' => ['Minimum commitment months must be greater than or equal to 0.']
            ]);
        }

        // Prohibit other arbitrary junk fields
        foreach ($snapshot as $key => $value) {
            if (!in_array($key, $requiredKeys, true)) {
                throw ValidationException::withMessages([
                    'terms_snapshot' => ["Arbitrary terms snapshot field '{$key}' is prohibited."]
                ]);
            }
        }

        return $cleanSnapshot;
    }

    /**
     * Validate unique package_code per tenant.
     */
    protected function validatePackageCodeUniqueness(int $tenantId, string $code, ?int $excludePackageId = null): void
    {
        $query = ServicePackage::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('package_code', trim($code));

        if ($excludePackageId !== null) {
            $query->where('id', '!=', $excludePackageId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'package_code' => ['The Package Code must be unique within this tenant context.']
            ]);
        }
    }

    /**
     * Validate speeds and logical burst consistency for Service Packages.
     */
    protected function validateAndNormalizePackage(array $data, array $existing = []): array
    {
        $download = (int)($data['download_speed_kbps'] ?? $existing['download_speed_kbps'] ?? 0);
        $upload = (int)($data['upload_speed_kbps'] ?? $existing['upload_speed_kbps'] ?? 0);

        // 1. Speeds must be greater than 0
        if ($download <= 0) {
            throw ValidationException::withMessages([
                'download_speed_kbps' => ['Download speed must be greater than 0 Kbps.']
            ]);
        }
        if ($upload <= 0) {
            throw ValidationException::withMessages([
                'upload_speed_kbps' => ['Upload speed must be greater than 0 Kbps.']
            ]);
        }

        // 2. Burst logical consistency
        $burstKeys = ['burst_download_kbps', 'burst_upload_kbps', 'burst_threshold_kb', 'burst_time_seconds'];
        $hasAnyBurst = false;

        foreach ($burstKeys as $key) {
            if (isset($data[$key]) && (int)$data[$key] > 0) {
                $hasAnyBurst = true;
                break;
            }
        }

        if ($hasAnyBurst) {
            // ALL burst values must be present and greater than 0
            foreach ($burstKeys as $key) {
                $val = (int)($data[$key] ?? $existing[$key] ?? 0);
                if ($val <= 0) {
                    throw ValidationException::withMessages([
                        $key => ["Logical Consistency Check Failed: '{$key}' must be greater than 0 when using burst capabilities."]
                    ]);
                }
            }

            // Burst download speed must be strictly greater than standard download speed
            $burstDown = (int)($data['burst_download_kbps'] ?? $existing['burst_download_kbps']);
            if ($burstDown <= $download) {
                throw ValidationException::withMessages([
                    'burst_download_kbps' => ['Burst download speed must be strictly greater than standard download speed.']
                ]);
            }

            // Burst upload speed must be strictly greater than standard upload speed
            $burstUp = (int)($data['burst_upload_kbps'] ?? $existing['burst_upload_kbps']);
            if ($burstUp <= $upload) {
                throw ValidationException::withMessages([
                    'burst_upload_kbps' => ['Burst upload speed must be strictly greater than standard upload speed.']
                ]);
            }
        }

        // 3. Customer Type Compatibility enum validation
        if (isset($data['customer_type_compatibility'])) {
            $compatList = (array)$data['customer_type_compatibility'];
            if (empty($compatList)) {
                throw ValidationException::withMessages([
                    'customer_type_compatibility' => ['At least one compatible customer type is required.']
                ]);
            }

            foreach ($compatList as $compatType) {
                if (CustomerType::tryFrom((string)$compatType) === null) {
                    throw ValidationException::withMessages([
                        'customer_type_compatibility' => ["Invalid compatible customer type: '{$compatType}'."]
                    ]);
                }
            }
        }

        return $data;
    }
}
