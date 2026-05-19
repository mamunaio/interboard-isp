<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Core\Settings\SettingsRegistry;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Enums\SubscriptionStatus;
use Carbon\Carbon;

class SuspensionEvaluationService
{
    public function __construct(
        protected SettingsRegistry $settingsRegistry
    ) {}

    /**
     * Evaluate if a subscription is eligible for suspension.
     */
    public function evaluateSuspension(CustomerServiceSubscription $subscription, mixed $today = null): SuspensionEvaluationResult
    {
        $today = $today ? Carbon::parse($today)->startOfDay() : Carbon::today();
        $tenantId = (int)$subscription->tenant_id;

        // 1. Subscription must exist and have ACTIVE status
        if ($subscription->status !== SubscriptionStatus::ACTIVE) {
            return new SuspensionEvaluationResult(
                eligible: false,
                reasons: ["Subscription is not active (current status: {$subscription->status->value})."],
                triggeringInvoiceIds: []
            );
        }

        // Get grace days from settings, hardcode default of 3 if not present
        $graceDays = (int)$this->settingsRegistry->get('suspension_grace_days', 3);

        // 2 & 3 & 4. Invoices check
        $triggeringInvoices = Invoice::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('customer_service_subscription_id', $subscription->id)
            ->whereIn('status', [InvoiceStatus::ISSUED->value, InvoiceStatus::PARTIALLY_PAID->value])
            ->get()
            ->filter(function (Invoice $invoice) use ($graceDays, $today) {
                // Must have amount_due > 0
                if (bccomp((string)$invoice->amount_due, '0.0000', 4) <= 0) {
                    return false;
                }

                // invoice due_date + suspension_grace_days < today
                $dueDate = Carbon::parse($invoice->due_date)->startOfDay();
                $dueDateWithGrace = $dueDate->copy()->addDays($graceDays);

                return $dueDateWithGrace->lessThan($today);
            });

        if ($triggeringInvoices->isEmpty()) {
            return new SuspensionEvaluationResult(
                eligible: false,
                reasons: ['No overdue unpaid invoices exceeding grace days found.'],
                triggeringInvoiceIds: []
            );
        }

        $triggeringIds = $triggeringInvoices->pluck('id')->toArray();

        return new SuspensionEvaluationResult(
            eligible: true,
            reasons: ["Subscription has {$triggeringInvoices->count()} overdue unpaid invoice(s) exceeding grace days."],
            triggeringInvoiceIds: $triggeringIds
        );
    }

    /**
     * Evaluate if a subscription is eligible for reactivation.
     */
    public function evaluateReactivation(CustomerServiceSubscription $subscription, mixed $today = null): SuspensionEvaluationResult
    {
        $today = $today ? Carbon::parse($today)->startOfDay() : Carbon::today();
        $tenantId = (int)$subscription->tenant_id;

        // 1. Subscription must exist and have SUSPENDED status
        if ($subscription->status !== SubscriptionStatus::SUSPENDED) {
            return new SuspensionEvaluationResult(
                eligible: false,
                reasons: ["Subscription is not suspended (current status: {$subscription->status->value})."],
                triggeringInvoiceIds: []
            );
        }

        // Get grace days from settings, hardcode default of 3 if not present
        $graceDays = (int)$this->settingsRegistry->get('suspension_grace_days', 3);

        // 2. ALL overdue eligible invoices must have amount_due = 0 (paid)
        // Find overdue invoices for this subscription that still have amount_due > 0
        $unpaidOverdueInvoices = Invoice::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('customer_service_subscription_id', $subscription->id)
            ->whereIn('status', [InvoiceStatus::ISSUED->value, InvoiceStatus::PARTIALLY_PAID->value])
            ->get()
            ->filter(function (Invoice $invoice) use ($graceDays, $today) {
                if (bccomp((string)$invoice->amount_due, '0.0000', 4) <= 0) {
                    return false;
                }

                $dueDate = Carbon::parse($invoice->due_date)->startOfDay();
                $dueDateWithGrace = $dueDate->copy()->addDays($graceDays);

                return $dueDateWithGrace->lessThan($today);
            });

        if ($unpaidOverdueInvoices->isNotEmpty()) {
            return new SuspensionEvaluationResult(
                eligible: false,
                reasons: ['Reactivation blocked: subscription has outstanding overdue invoices.'],
                triggeringInvoiceIds: $unpaidOverdueInvoices->pluck('id')->toArray()
            );
        }

        return new SuspensionEvaluationResult(
            eligible: true,
            reasons: ['All overdue invoices have been fully paid.'],
            triggeringInvoiceIds: []
        );
    }
}
