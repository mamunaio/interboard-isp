<?php

namespace Database\Seeders;

use App\Core\Tenant\TenantManager;
use App\Modules\Billing\Enums\BillingCycleType;
use App\Modules\Billing\Enums\BillingMode;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Billing\Models\FinancialPeriod;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Services\AccountingPostingService;
use App\Modules\Customer\Enums\CustomerStatus;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\ServiceType;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Models\ServicePlan;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoEnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Demo Tenant
        $plan = Plan::firstOrCreate(
            ['slug' => 'enterprise'],
            ['name' => 'Enterprise Plan', 'price_monthly' => 5000.00, 'price_yearly' => 50000.00]
        );

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo-isp'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Demo ISP Network',
                'status' => 'active',
                'plan_id' => $plan->id,
            ]
        );

        app(TenantManager::class)->setTenant($tenant);

        // 2. Open Financial Period
        FinancialPeriod::firstOrCreate(
            ['tenant_id' => $tenant->id, 'period_name' => now()->format('M Y')],
            ['start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->endOfMonth()->toDateString(), 'status' => 'open']
        );

        // 3. Create Service Plan
        $servicePlan = ServicePlan::firstOrCreate(
            ['tenant_id' => $tenant->id, 'plan_code' => 'DEMO-100M'],
            [
                'name' => '100 Mbps Extreme',
                'monthly_price' => '2500.0000',
                'billing_cycle' => 'monthly',
                'download_speed' => '100 Mbps',
                'upload_speed' => '50 Mbps',
                'is_active' => true,
            ]
        );

        // 4. Create Demo Customer
        $customer = Customer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'demo.user@example.com'],
            [
                'customer_code' => 'DEMO-001',
                'full_name' => 'Demo User Rahman',
                'phone' => '01700000000',
                'status' => CustomerStatus::ACTIVE,
                'type' => CustomerType::RESIDENTIAL,
            ]
        );

        // 5. Assign Subscription
        $subscription = CustomerServiceSubscription::firstOrCreate(
            ['tenant_id' => $tenant->id, 'customer_id' => $customer->id],
            [
                'service_plan_id' => $servicePlan->id,
                'subscription_code' => 'SUB-DEMO-001',
                'service_type_snapshot' => ServiceType::BROADBAND,
                'status' => SubscriptionStatus::ACTIVE,
                'billing_mode' => BillingMode::PREPAID,
                'billing_cycle_type' => BillingCycleType::ANNIVERSARY,
                'next_billing_date' => now()->addMonth()->toDateString(),
                'activated_at' => now(),
            ]
        );

        // 6. Generate Demo Invoices
        $invoice1 = Invoice::firstOrCreate(
            ['tenant_id' => $tenant->id, 'invoice_number' => 'INV-DEMO-001'],
            [
                'customer_id' => $customer->id,
                'invoice_type' => InvoiceType::SUBSCRIPTION,
                'customer_service_subscription_id' => $subscription->id,
                'issue_date' => now()->subDays(5)->toDateString(),
                'due_date' => now()->toDateString(),
                'status' => InvoiceStatus::ISSUED,
                'subtotal' => 2500,
                'grand_total' => 2500,
                'amount_due' => 2500,
            ]
        );

        $invoice2 = Invoice::firstOrCreate(
            ['tenant_id' => $tenant->id, 'invoice_number' => 'INV-DEMO-002'],
            [
                'customer_id' => $customer->id,
                'invoice_type' => InvoiceType::SUBSCRIPTION,
                'customer_service_subscription_id' => $subscription->id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(5)->toDateString(),
                'status' => InvoiceStatus::ISSUED,
                'subtotal' => 1500, // Extra addon or something
                'grand_total' => 1500,
                'amount_due' => 1500,
            ]
        );

        $accountingService = app(AccountingPostingService::class);

        if (\App\Modules\Billing\Models\JournalEntry::where('source_type', Invoice::class)->where('source_id', $invoice1->id)->count() === 0) {
            $accountingService->postInvoice($invoice1);
        }
        if (\App\Modules\Billing\Models\JournalEntry::where('source_type', Invoice::class)->where('source_id', $invoice2->id)->count() === 0) {
            $accountingService->postInvoice($invoice2);
        }

        // 7. Process a Payment for Invoice 1
        $wallet = \App\Modules\Billing\Models\Wallet::firstOrCreate(
            ['tenant_id' => $tenant->id, 'owner_type' => Customer::class, 'owner_id' => $customer->id],
            ['current_balance' => 0, 'currency' => 'BDT']
        );
        $ledgerEntry = \App\Modules\Billing\Models\LedgerEntry::firstOrCreate(
            ['tenant_id' => $tenant->id, 'wallet_id' => $wallet->id, 'reference_type' => 'demo', 'reference_id' => 1],
            ['transaction_type' => 'manual_payment_receipt', 'direction' => 'credit', 'amount' => 2500, 'balance_before' => 0, 'balance_after' => 2500]
        );

        $receipt = PaymentReceipt::firstOrCreate(
            ['tenant_id' => $tenant->id, 'receipt_number' => 'RCT-DEMO-001'],
            [
                'customer_id' => $customer->id,
                'amount' => 2500,
                'payment_method' => 'bkash_gateway',
                'wallet_ledger_entry_id' => $ledgerEntry->id,
                'created_at' => now()->subDays(1),
            ]
        );

        if (\App\Modules\Billing\Models\JournalEntry::where('source_type', PaymentReceipt::class)->where('source_id', $receipt->id)->count() === 0) {
            $accountingService->postPayment($receipt);
        }
    }
}
