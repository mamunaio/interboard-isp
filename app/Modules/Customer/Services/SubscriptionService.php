<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Models\SubscriptionScheduledChange;
use App\Modules\Customer\Models\ServicePackage;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Customer\Enums\ScheduledChangeStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    /**
     * Create a new Customer Service Subscription safely.
     */
    public function createSubscription(int $tenantId, int $customerId, array $data): CustomerServiceSubscription
    {
        // 1. Verify tenant-scoped Customer ownership strictly
        $customer = $this->resolveAndValidateCustomer($tenantId, $customerId);

        // 2. Verify tenant-scoped Service Package ownership strictly
        $package = $this->resolveAndValidateServicePackage($tenantId, (int)($data['service_package_id'] ?? 0));

        // 3. Strict Creation Constraint: Subscriptions MUST be created with status pending
        $status = isset($data['status'])
            ? ($data['status'] instanceof SubscriptionStatus ? $data['status'] : SubscriptionStatus::from($data['status']))
            : SubscriptionStatus::PENDING;

        if ($status !== SubscriptionStatus::PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Subscriptions must strictly be created with a status of pending. Direct activation or other states are blocked.']
            ]);
        }

        // 4. Derive service_type_snapshot dynamically from linked package
        $data['service_type_snapshot'] = $package->service_type;

        // 5. Generate deterministic, collision-safe subscription code (Database pessimistic lock)
        $data['subscription_code'] = $this->generateSubscriptionCode($tenantId);

        // 6. Force tenant ownership
        $data['tenant_id'] = $customer->tenant_id;
        $data['customer_id'] = $customer->id;
        $data['status'] = SubscriptionStatus::PENDING;

        return CustomerServiceSubscription::create($data);
    }

    /**
     * Transition a subscription's lifecycle status securely with active concurrency limits and timestamp rules.
     */
    public function transitionSubscriptionStatus(CustomerServiceSubscription $subscription, SubscriptionStatus $newStatus): CustomerServiceSubscription
    {
        $currentStatus = $subscription->status;

        // 1. Enforce explicit transition rules
        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => ["Invalid status transition: Cannot transition from '{$currentStatus->value}' to '{$newStatus->value}'."]
            ]);
        }

        // 2. Active Concurrency Validation Rule (One active/suspended subscription per same service type per customer)
        if ($newStatus === SubscriptionStatus::ACTIVE || $newStatus === SubscriptionStatus::SUSPENDED) {
            $duplicateExists = CustomerServiceSubscription::withoutGlobalScopes()
                ->where('customer_id', $subscription->customer_id)
                ->where('id', '!=', $subscription->id)
                ->where('service_type_snapshot', $subscription->service_type_snapshot->value)
                ->whereIn('status', [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::SUSPENDED->value])
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'status' => ["The customer already has an active or suspended subscription of service type '{$subscription->service_type_snapshot->value}'."]
                ]);
            }
        }

        $updateData = ['status' => $newStatus];

        // 3. Reactivation and lifecycle timestamp rules
        if ($newStatus === SubscriptionStatus::ACTIVE) {
            if ($subscription->activated_at === null) {
                $updateData['activated_at'] = now();
            }
            // Clear transient lifecycle timestamps to ensure a completely clean status record
            $updateData['suspended_at'] = null;
            $updateData['disconnected_at'] = null;
        } elseif ($newStatus === SubscriptionStatus::SUSPENDED) {
            $updateData['suspended_at'] = now();
        } elseif ($newStatus === SubscriptionStatus::DISCONNECTED) {
            $updateData['disconnected_at'] = now();
        } elseif ($newStatus === SubscriptionStatus::TERMINATED) {
            $updateData['terminated_at'] = now();
        }

        $subscription->update($updateData);

        return $subscription;
    }

    /**
     * Schedule upgrade/downgrade under validated payloads.
     */
    public function scheduleSubscriptionChange(CustomerServiceSubscription $subscription, array $data): SubscriptionScheduledChange
    {
        $tenantId = (int)$subscription->tenant_id;

        // 1. Validate target service package exists and matches tenant
        $targetPackage = $this->resolveAndValidateServicePackage($tenantId, (int)($data['target_service_package_id'] ?? 0));

        // 2. Validate payload schema structure (no arbitrary JSON junk)
        $payload = isset($data['payload']) ? $this->validateScheduledPayload($data['payload']) : null;

        return SubscriptionScheduledChange::create([
            'tenant_id' => $tenantId,
            'customer_service_subscription_id' => $subscription->id,
            'target_service_package_id' => $targetPackage->id,
            'scheduled_for' => $data['scheduled_for'],
            'payload' => $payload,
            'status' => ScheduledChangeStatus::PENDING,
        ]);
    }

    /**
     * Cancel a scheduled change safely.
     */
    public function cancelScheduledChange(SubscriptionScheduledChange $change): SubscriptionScheduledChange
    {
        if ($change->status !== ScheduledChangeStatus::PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Only pending scheduled changes can be cancelled.']
            ]);
        }

        $change->update([
            'status' => ScheduledChangeStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);

        return $change;
    }

    /**
     * Execute a scheduled change safely, updating the parent subscription package.
     */
    public function executeScheduledChange(SubscriptionScheduledChange $change): SubscriptionScheduledChange
    {
        if ($change->status !== ScheduledChangeStatus::PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Only pending scheduled changes can be executed.']
            ]);
        }

        DB::transaction(function () use ($change) {
            $change->update([
                'status' => ScheduledChangeStatus::EXECUTED,
                'executed_at' => now(),
            ]);

            $change->subscription->update([
                'service_package_id' => $change->target_service_package_id,
            ]);
        });

        return $change;
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
     * Retrieve and validate that the service package belongs to the active tenant strictly.
     */
    protected function resolveAndValidateServicePackage(int $tenantId, int $packageId): ServicePackage
    {
        $package = ServicePackage::withoutGlobalScopes()
            ->where('id', $packageId)
            ->first();

        if (!$package || (int)$package->tenant_id !== $tenantId) {
            throw ValidationException::withMessages([
                'service_package_id' => ['The selected service package does not belong to the active tenant context.']
            ]);
        }

        return $package;
    }

    /**
     * Generate deterministic, collision-safe subscription codes using database transaction-locked sequences.
     */
    public function generateSubscriptionCode(int $tenantId): string
    {
        $year = date('Y');

        $sequence = DB::transaction(function () use ($tenantId, $year) {
            $seq = DB::table('subscription_code_sequences')
                ->where('tenant_id', $tenantId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                DB::table('subscription_code_sequences')->insert([
                    'tenant_id' => $tenantId,
                    'year' => $year,
                    'last_value' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return 1;
            }

            $newValue = (int)$seq->last_value + 1;
            
            DB::table('subscription_code_sequences')
                ->where('tenant_id', $tenantId)
                ->where('year', $year)
                ->update([
                    'last_value' => $newValue,
                    'updated_at' => now(),
                ]);

            return $newValue;
        });

        $seqFormatted = str_pad((string)$sequence, 5, '0', STR_PAD_LEFT);
        
        return "SUB-{$year}-{$seqFormatted}";
    }

    /**
     * Validate and normalize structured scheduled change payload to prevent arbitrary JSON junk.
     * Required optional keys: reason (string)
     */
    protected function validateScheduledPayload(array $payload): array
    {
        $allowedKeys = ['reason'];
        $cleanPayload = [];

        foreach ($payload as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                throw ValidationException::withMessages([
                    'payload' => ["Arbitrary payload field '{$key}' is prohibited."]
                ]);
            }
            $cleanPayload[$key] = trim((string)$value);
        }

        return $cleanPayload;
    }
}
