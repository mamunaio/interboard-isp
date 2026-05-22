<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerLifecycleEvent;
use App\Modules\Customer\Enums\CustomerStatus;
use Illuminate\Support\Facades\DB;
use App\Modules\Provisioning\Jobs\ProcessProvisioningJob;
use App\Modules\Customer\Models\CustomerServiceSubscription;

class CustomerLifecycleService
{
    /**
     * Activate a customer from Lead/Prospect to Active.
     */
    public function activateCustomer(Customer $customer, string $reason, $changedBy = null): void
    {
        $this->transition($customer, CustomerStatus::ACTIVE->value, $reason, $changedBy, function () use ($customer) {
            // Trigger provisioning for all active subscriptions
            $subscriptions = CustomerServiceSubscription::where('customer_id', $customer->id)
                ->where('status', 'active')
                ->get();

            foreach ($subscriptions as $sub) {
                // Find associated provisioning event or create one to sync with router
                $event = $sub->provisioningEvents()->latest()->first();
                if ($event) {
                    ProcessProvisioningJob::dispatch($event->id);
                }
            }
        });
    }

    /**
     * Suspend an active customer.
     */
    public function suspendCustomer(Customer $customer, string $reason, $changedBy = null): void
    {
        $this->transition($customer, CustomerStatus::SUSPENDED->value, $reason, $changedBy, function () use ($customer) {
            // Re-sync provisioning (suspension is handled in the ProcessProvisioningJob logic usually)
            $subscriptions = CustomerServiceSubscription::where('customer_id', $customer->id)->get();
            foreach ($subscriptions as $sub) {
                $sub->update(['status' => 'suspended']);
                $event = $sub->provisioningEvents()->latest()->first();
                if ($event) {
                    ProcessProvisioningJob::dispatch($event->id);
                }
            }
        });
    }

    /**
     * Churn (permanently disconnect) a customer.
     */
    public function churnCustomer(Customer $customer, string $reason, $changedBy = null, bool $recoverAssets = true): void
    {
        $this->transition($customer, CustomerStatus::CHURNED->value, $reason, $changedBy, function () use ($customer, $recoverAssets) {
            $subscriptions = CustomerServiceSubscription::where('customer_id', $customer->id)->get();
            foreach ($subscriptions as $sub) {
                $sub->update(['status' => 'churned']);
                // Disconnect immediately and remove from NAS
                $event = $sub->provisioningEvents()->latest()->first();
                if ($event) {
                    // Send to job for deletion/deactivation from Router
                    ProcessProvisioningJob::dispatch($event->id);
                }
            }

            if ($recoverAssets) {
                // Future integration: trigger Asset Management recovery workflow here.
            }
        });
    }

    /**
     * Core transition handler.
     */
    protected function transition(Customer $customer, string $newStatus, string $reason, $changedBy, callable $sideEffects): void
    {
        $previousStatus = $customer->status->value ?? 'lead';

        if ($previousStatus === $newStatus) {
            return; // No change needed
        }

        DB::transaction(function () use ($customer, $newStatus, $previousStatus, $reason, $changedBy, $sideEffects) {
            // Update customer status
            // Note: If CustomerStatus is an enum, we need to convert the string back to the enum.
            $customer->status = CustomerStatus::from($newStatus);
            $customer->save();

            // Log lifecycle event
            CustomerLifecycleEvent::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'changed_by_type' => $changedBy ? get_class($changedBy) : null,
                'changed_by_id' => $changedBy ? $changedBy->id : null,
            ]);

            // Execute side effects
            $sideEffects();
        });
    }
}
