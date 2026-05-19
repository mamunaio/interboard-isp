<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Core\Settings\SettingsRegistry;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\SuspensionEvent;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Provisioning\Models\ServiceProvisioningEvent;
use App\Modules\Provisioning\Enums\ProvisioningActionType;
use App\Modules\Provisioning\Enums\ProvisioningStatus;
use App\Modules\Provisioning\Jobs\ProcessProvisioningJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SuspensionActionService
{
    public function __construct(
        protected SuspensionEvaluationService $evaluationService,
        protected InvoiceService $invoiceService,
        protected SettingsRegistry $settingsRegistry
    ) {}

    /**
     * Suspend subscription securely under strict transaction isolation.
     */
    public function suspend(int $tenantId, int $subscriptionId, array $options = []): CustomerServiceSubscription
    {
        return DB::transaction(function () use ($tenantId, $subscriptionId, $options) {
            // 1. Lock subscription row
            $subscription = CustomerServiceSubscription::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find($subscriptionId);

            if (!$subscription) {
                throw ValidationException::withMessages([
                    'subscription' => ['Subscription not found or does not belong to the active tenant.']
                ]);
            }

            // Enforce tenant isolation explicitly
            if ((int)$subscription->tenant_id !== $tenantId) {
                throw ValidationException::withMessages([
                    'tenant_id' => ['Tenant mismatch.']
                ]);
            }

            // Block duplicate suspensions
            if ($subscription->status === SubscriptionStatus::SUSPENDED) {
                throw ValidationException::withMessages([
                    'subscription' => ['Subscription is already suspended.']
                ]);
            }

            $today = $options['today'] ?? null;

            // 2. Re-evaluate eligibility
            $evaluation = $this->evaluationService->evaluateSuspension($subscription, $today);
            if (!$evaluation->eligible) {
                throw ValidationException::withMessages([
                    'subscription' => $evaluation->reasons
                ]);
            }

            // 3. Insert immutable event
            SuspensionEvent::create([
                'tenant_id' => $tenantId,
                'customer_service_subscription_id' => $subscription->id,
                'action_type' => 'suspended',
                'reason' => $options['reason'] ?? 'Automatic suspension due to overdue outstanding dues.',
                'triggering_metadata' => [
                    'triggering_invoice_ids' => $evaluation->triggeringInvoiceIds,
                    'evaluation_today' => $today ? Carbon::parse($today)->toDateString() : Carbon::today()->toDateString(),
                ],
                'created_by_type' => $options['created_by_type'] ?? null,
                'created_by_id' => $options['created_by_id'] ?? null,
            ]);

            // 4. Transition subscription -> suspended
            $subscription->status = SubscriptionStatus::SUSPENDED;
            $subscription->save();

            // 5. Dispatch Provisioning Event to disconnect the user
            $provisioningEvent = ServiceProvisioningEvent::create([
                'tenant_id' => $tenantId,
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'action_type' => ProvisioningActionType::SUSPEND,
                'status' => ProvisioningStatus::PENDING,
                'provider' => 'mikrotik',
                'request_payload_json' => ['username' => $subscription->subscription_code],
            ]);

            ProcessProvisioningJob::dispatch($provisioningEvent->id);

            return $subscription;
        });

        $subscription->load('customer');
        event(new \App\Modules\Tenant\Events\SuspensionTriggered(
            $subscription->subscription_code,
            $subscription->customer->full_name,
            $options['reason'] ?? 'Automatic suspension due to overdue outstanding dues.',
            now()->toDateTimeString()
        ));

        return $subscription;
    }

    /**
     * Reactivate subscription securely under strict transaction isolation.
     */
    public function reactivate(int $tenantId, int $subscriptionId, array $options = []): CustomerServiceSubscription
    {
        return DB::transaction(function () use ($tenantId, $subscriptionId, $options) {
            // 1. Lock subscription row
            $subscription = CustomerServiceSubscription::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find($subscriptionId);

            if (!$subscription) {
                throw ValidationException::withMessages([
                    'subscription' => ['Subscription not found or does not belong to the active tenant.']
                ]);
            }

            // Enforce tenant isolation explicitly
            if ((int)$subscription->tenant_id !== $tenantId) {
                throw ValidationException::withMessages([
                    'tenant_id' => ['Tenant mismatch.']
                ]);
            }

            // Block duplicate active states (only suspended subscriptions can be reactivated)
            if ($subscription->status !== SubscriptionStatus::SUSPENDED) {
                throw ValidationException::withMessages([
                    'subscription' => ['Subscription must be in suspended status to reactivate.']
                ]);
            }

            $today = $options['today'] ?? null;

            // 2. Re-evaluate eligibility
            $evaluation = $this->evaluationService->evaluateReactivation($subscription, $today);
            if (!$evaluation->eligible) {
                throw ValidationException::withMessages([
                    'subscription' => $evaluation->reasons
                ]);
            }

            // 3. Reconnection Fee Invoice Generation
            $feeEnabled = (bool)$this->settingsRegistry->get('reconnection_fee_enabled', false);
            $feeAmount = $this->settingsRegistry->get('reconnection_fee_amount', '0.0000');

            if (isset($options['reconnection_fee_enabled'])) {
                $feeEnabled = (bool)$options['reconnection_fee_enabled'];
            }
            if (isset($options['reconnection_fee_amount'])) {
                $feeAmount = $options['reconnection_fee_amount'];
            }

            if ($feeEnabled && bccomp((string)$feeAmount, '0.0000', 4) === -1) {
                throw ValidationException::withMessages([
                    'reconnection_fee_amount' => ['Reconnection fee amount must be greater than or equal to 0.0000.']
                ]);
            }

            $reconnectionInvoice = null;

            if ($feeEnabled && bccomp((string)$feeAmount, '0.0000', 4) === 1) {
                // Find latest suspension event for deterministic duplicate protection WITH lock
                $latestSuspension = SuspensionEvent::where('customer_service_subscription_id', $subscription->id)
                    ->where('action_type', 'suspended')
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($latestSuspension) {
                    $alreadyBilled = SuspensionEvent::where('customer_service_subscription_id', $subscription->id)
                        ->where('action_type', 'reactivated')
                        ->where('id', '>', $latestSuspension->id)
                        ->whereNotNull('reconnection_invoice_id')
                        ->lockForUpdate()
                        ->exists();

                    if ($alreadyBilled) {
                        throw ValidationException::withMessages([
                            'subscription' => ['Reconnection fee has already been generated for the current suspension cycle.']
                        ]);
                    }
                }

                $issueDate = $options['issue_date'] ?? Carbon::today()->toDateString();
                $dueDate = $options['due_date'] ?? Carbon::today()->toDateString();

                $lineItems = [
                    [
                        'line_type' => 'service',
                        'description' => 'Reconnection Fee',
                        'quantity' => '1.0000',
                        'unit_price' => bcadd((string)$feeAmount, '0', 4),
                    ]
                ];

                // Create draft invoice using existing invoice engine
                $invoice = $this->invoiceService->createInvoice($tenantId, (int)$subscription->customer_id, [
                    'invoice_type' => InvoiceType::RECONNECTION_FEE->value,
                    'customer_service_subscription_id' => $subscription->id,
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'notes' => 'Reconnection fee for subscription reactivation.',
                ], $lineItems);

                // Transition to ISSUED
                $invoice = $this->invoiceService->transitionInvoiceStatus($invoice, InvoiceStatus::ISSUED);

                $reconnectionInvoice = $invoice;
            }

            // 4. Insert immutable event
            SuspensionEvent::create([
                'tenant_id' => $tenantId,
                'customer_service_subscription_id' => $subscription->id,
                'action_type' => 'reactivated',
                'reason' => $options['reason'] ?? 'Subscription reactivated after dues settlement.',
                'triggering_metadata' => [
                    'evaluation_today' => $today ? Carbon::parse($today)->toDateString() : Carbon::today()->toDateString(),
                ],
                'reconnection_invoice_id' => $reconnectionInvoice ? $reconnectionInvoice->id : null,
                'created_by_type' => $options['created_by_type'] ?? null,
                'created_by_id' => $options['created_by_id'] ?? null,
            ]);

            // 5. Transition subscription -> active
            $subscription->status = SubscriptionStatus::ACTIVE;
            $subscription->save();

            // 6. Dispatch Provisioning Event to reconnect the user
            $provisioningEvent = ServiceProvisioningEvent::create([
                'tenant_id' => $tenantId,
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'action_type' => ProvisioningActionType::REACTIVATE,
                'status' => ProvisioningStatus::PENDING,
                'provider' => 'mikrotik',
                'request_payload_json' => ['username' => $subscription->subscription_code],
            ]);

            ProcessProvisioningJob::dispatch($provisioningEvent->id);

            return $subscription;
        });
    }
}
