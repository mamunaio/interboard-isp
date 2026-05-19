<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Billing\Enums\BillingMode;
use App\Modules\Billing\Enums\BillingCycleType;
use App\Modules\Billing\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BillingGenerationService
{
    /**
     * Generate an invoice automatically for an active customer service subscription.
     */
    public function generateInvoiceForSubscription(int $tenantId, int $subscriptionId, string $runDate): Invoice
    {
        return DB::transaction(function () use ($tenantId, $subscriptionId, $runDate) {
            // 1. Row-level pessimistic lock to prevent concurrent runs on the same subscription
            $subscription = CustomerServiceSubscription::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($subscriptionId);

            // 2. Strict status check: Only active subscriptions can be billed
            if ($subscription->status->value !== 'active') {
                throw ValidationException::withMessages([
                    'subscription_id' => ["Only active subscriptions are eligible for billing. Current status: '{$subscription->status->value}'."]
                ]);
            }

            // 3. Strict validation: no null billing dates
            if ($subscription->next_billing_date === null) {
                throw ValidationException::withMessages([
                    'next_billing_date' => ['Subscription next billing date cannot be null.']
                ]);
            }

            // 4. Strict Calendar Mode Invariant check: next_billing_date must be on day 1
            if ($subscription->billing_cycle_type === BillingCycleType::CALENDAR) {
                $nextCarbon = Carbon::parse($subscription->next_billing_date);
                if ($nextCarbon->day !== 1) {
                    throw ValidationException::withMessages([
                        'next_billing_date' => ["For calendar billing cycles, next_billing_date must align to the 1st of the month. Found: {$nextCarbon->toDateString()}"]
                    ]);
                }
            }

            // 5. Compute billing periods and the advanced next billing date
            $next = Carbon::parse($subscription->next_billing_date);
            $billingPeriodStart = null;
            $billingPeriodEnd = null;
            $newNextBillingDate = null;

            if ($subscription->billing_cycle_type === BillingCycleType::CALENDAR) {
                if ($subscription->billing_mode === BillingMode::PREPAID) {
                    $billingPeriodStart = $next->copy()->startOfMonth();
                    $billingPeriodEnd = $next->copy()->endOfMonth();
                    $newNextBillingDate = $next->copy()->addMonth()->startOfMonth();
                } else { // POSTPAID
                    $billingPeriodEnd = $next->copy()->subDay()->endOfMonth();
                    $billingPeriodStart = $billingPeriodEnd->copy()->startOfMonth();
                    $newNextBillingDate = $next->copy()->addMonth()->startOfMonth();
                }
            } else { // ANNIVERSARY
                if ($subscription->billing_mode === BillingMode::PREPAID) {
                    $billingPeriodStart = $next->copy();
                    $billingPeriodEnd = $next->copy()->addMonthNoOverflow()->subDay();
                    $newNextBillingDate = $next->copy()->addMonthNoOverflow();
                } else { // POSTPAID
                    $billingPeriodEnd = $next->copy()->subDay();
                    $billingPeriodStart = $next->copy()->subMonthNoOverflow();
                    $newNextBillingDate = $next->copy()->addMonthNoOverflow();
                }
            }

            // 6. DB-level uniqueness checks before draft creation (duplicate generation prevention)
            $exists = Invoice::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('customer_service_subscription_id', $subscriptionId)
                ->whereDate('billing_period_start', $billingPeriodStart->toDateString())
                ->whereDate('billing_period_end', $billingPeriodEnd->toDateString())
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'duplicate' => ["An invoice already exists for subscription ID {$subscriptionId} covering the period {$billingPeriodStart->toDateString()} to {$billingPeriodEnd->toDateString()}."]
                ]);
            }

            // 7. Validate package/plan existence and monthly price
            $package = $subscription->servicePlan ?? $subscription->servicePackage;
            if (!$package) {
                throw ValidationException::withMessages([
                    'service_package_id' => ['The service package or plan linked to this subscription does not exist.']
                ]);
            }

            if ((int)$package->tenant_id !== $tenantId) {
                throw ValidationException::withMessages([
                    'tenant_id' => ['Tenant mismatch on the linked subscription service package/plan.']
                ]);
            }

            // Ensure package price is a valid non-negative string decimal
            $price = (string)($package instanceof \App\Modules\Customer\Models\ServicePlan ? $package->monthly_price : $package->price_monthly);
            if (bccomp($price, '0', 4) === -1) {
                throw ValidationException::withMessages([
                    'price_monthly' => ["Monthly pricing for service package/plan is invalid: '{$price}'."]
                ]);
            }

            // 8. Construct deterministic invoice description
            $description = "Monthly Service Fee for {$package->name} ({$billingPeriodStart->format('Y-m-d')} to {$billingPeriodEnd->format('Y-m-d')})";

            // 9. Two-step creation process (DRAFT -> ISSUED) due to InvoiceService limitations
            $invoiceService = app(InvoiceService::class);
            
            $invoiceData = [
                'customer_service_subscription_id' => $subscriptionId,
                'invoice_type' => InvoiceType::SUBSCRIPTION->value,
                'issue_date' => $runDate,
                'due_date' => Carbon::parse($runDate)->addDays(10)->toDateString(), // 10 days grace period
                'currency' => 'BDT',
                'notes' => "Automated invoice generation for period {$billingPeriodStart->toDateString()} to {$billingPeriodEnd->toDateString()}.",
                'metadata' => [
                    'billing_period_start' => $billingPeriodStart->toDateString(),
                    'billing_period_end' => $billingPeriodEnd->toDateString(),
                ]
            ];

            $lineItems = [
                [
                    'line_type' => 'service',
                    'description' => $description,
                    'quantity' => '1.0000',
                    'unit_price' => $price,
                ]
            ];

            // Creates draft invoice and computes subtotal, sequence, and tax safely
            $invoice = $invoiceService->createInvoice($tenantId, (int)$subscription->customer_id, $invoiceData, $lineItems);

            // 10. Transition status to ISSUED and update dues atomically
            $invoice->amount_paid = '0.0000';
            $invoice->amount_due = $invoice->grand_total;
            $invoice->status = InvoiceStatus::ISSUED;
            $invoice->billing_period_start = $billingPeriodStart;
            $invoice->billing_period_end = $billingPeriodEnd;
            $invoice->save();

            // 11. Advance next_billing_date and set last_billed_at on active subscription
            $subscription->last_billed_at = now();
            $subscription->next_billing_date = $newNextBillingDate;
            $subscription->save();

            return $invoice;
        });
    }
}
