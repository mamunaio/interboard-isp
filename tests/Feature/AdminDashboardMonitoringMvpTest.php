<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Models\ServicePlan;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Customer\Enums\CustomerStatus;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\LedgerEntry;
use App\Modules\Billing\Models\SuspensionEvent;
use App\Modules\Billing\Models\ReversalEvent;
use App\Modules\Billing\Models\GatewayPaymentAttempt;
use App\Modules\Billing\Enums\GatewayPaymentStatus;
use App\Modules\Tenant\Models\AuditLog;
use App\Modules\Tenant\Http\Livewire\Dashboard;
use App\Modules\Tenant\Http\Livewire\AuditTimeline;
use App\Modules\Tenant\Http\Livewire\GlobalSearch;
use App\Modules\Customer\Http\Livewire\CustomerDetail;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminDashboardMonitoringMvpTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenant;
    protected TenantManager $tenantManager;
    protected Customer $customer;
    protected CustomerServiceSubscription $subscription;
    protected ServicePlan $servicePlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Starter Plan',
            'slug' => 'starter',
            'price_monthly' => 100.00,
            'price_yearly' => 1000.00,
        ]);

        $this->tenant = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'slug' => 'alpha-isp',
            'name' => 'Alpha ISP Provider',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->tenantManager = app(TenantManager::class);
        $this->tenantManager->setTenant($this->tenant);

        // Create Customer
        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'customer_code' => 'CUST-303',
            'full_name' => 'Jane Monitoring Doe',
            'email' => 'jane.mon@example.com',
            'phone' => '01700000303',
            'status' => 'active',
            'type' => \App\Modules\Customer\Enums\CustomerType::RESIDENTIAL,
        ]);

        // Service Plan
        $this->servicePlan = ServicePlan::create([
            'tenant_id' => $this->tenant->id,
            'plan_code' => 'SP-10M',
            'name' => '10 Mbps Standard',
            'monthly_price' => '500.0000',
            'billing_cycle' => 'monthly',
            'download_speed' => '10 Mbps',
            'upload_speed' => '5 Mbps',
            'is_active' => true,
        ]);

        // Subscription
        $this->subscription = CustomerServiceSubscription::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'service_plan_id' => $this->servicePlan->id,
            'subscription_code' => 'SUB-999-001',
            'service_type_snapshot' => \App\Modules\Customer\Enums\ServiceType::BROADBAND,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_mode' => \App\Modules\Billing\Enums\BillingMode::PREPAID,
            'billing_cycle_type' => \App\Modules\Billing\Enums\BillingCycleType::ANNIVERSARY,
            'next_billing_date' => now()->addMonth()->toDateString(),
            'activated_at' => now(),
        ]);
    }

    protected function createReceipt(string $receiptNumber, string $amount, string $method = 'cash'): PaymentReceipt
    {
        $wallet = Wallet::firstOrCreate([
            'tenant_id' => $this->tenant->id,
            'owner_type' => Customer::class,
            'owner_id' => $this->customer->id,
        ], [
            'currency' => 'BDT',
            'current_balance' => '0.0000',
        ]);

        $ledger = LedgerEntry::create([
            'tenant_id' => $this->tenant->id,
            'wallet_id' => $wallet->id,
            'transaction_type' => \App\Modules\Billing\Enums\TransactionType::MANUAL_PAYMENT_RECEIPT,
            'direction' => \App\Modules\Billing\Enums\TransactionDirection::CREDIT,
            'amount' => $amount,
            'balance_before' => '0.0000',
            'balance_after' => $amount,
        ]);

        return PaymentReceipt::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'receipt_number' => $receiptNumber,
            'amount' => $amount,
            'payment_method' => \App\Modules\Billing\Enums\PaymentMethod::from($method),
            'status' => 'posted',
            'wallet_ledger_entry_id' => $ledger->id,
            'currency' => 'BDT',
        ]);
    }

    /**
     * Test the main Monitoring dashboard widgets fetch real values.
     */
    public function test_dashboard_renders_with_real_stats_and_gateway_attempt_breakdowns(): void
    {
        // 1. Setup Wallet Balance
        Wallet::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => Customer::class,
            'owner_id' => $this->customer->id,
            'currency' => 'BDT',
            'current_balance' => '1250.0000',
        ]);

        // 2. Setup unpaid and overdue invoices
        Invoice::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'customer_service_subscription_id' => $this->subscription->id,
            'invoice_number' => 'INV-001',
            'invoice_type' => \App\Modules\Billing\Enums\InvoiceType::SUBSCRIPTION,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDays(2)->toDateString(), // overdue!
            'currency' => 'BDT',
            'subtotal' => '500.0000',
            'tax_total' => '0.0000',
            'discount_total' => '0.0000',
            'grand_total' => '500.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '500.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        // 3. Setup gateway payment attempts
        GatewayPaymentAttempt::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'gateway' => 'bkash',
            'attempt_reference' => 'ATT-BKASH-001',
            'requested_amount' => '500.0000',
            'currency' => 'BDT',
            'status' => GatewayPaymentStatus::VERIFIED->value,
        ]);

        // Renders dashboard Livewire component
        Livewire::test(Dashboard::class)
            ->assertSet('stats.active_customers', 1)
            ->assertSet('stats.active_subscriptions', 1)
            ->assertSet('stats.unpaid_invoices', 1)
            ->assertSet('stats.overdue_invoices', 1)
            ->assertSet('stats.total_wallet_balance', '1,250.00')
            ->assertSee('verified');
    }

    /**
     * Test Audit Activity Timeline shows operations telemetry.
     */
    public function test_audit_timeline_renders_and_filters_audit_logs(): void
    {
        Livewire::test(AuditTimeline::class)
            ->assertSee('Customer')
            ->assertSee('created')
            ->set('modelFilter', 'Customer')
            ->assertSee('Customer')
            ->set('modelFilter', 'Invoice')
            ->assertDontSee('Customer');
    }

    /**
     * Test Global Search queries all models correctly.
     */
    public function test_global_search_searches_customers_invoices_subscriptions_receipts(): void
    {
        // Setup Invoice & PaymentReceipt
        Invoice::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'customer_service_subscription_id' => $this->subscription->id,
            'invoice_number' => 'INV-SEARCH-101',
            'invoice_type' => \App\Modules\Billing\Enums\InvoiceType::SUBSCRIPTION,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'currency' => 'BDT',
            'subtotal' => '500.0000',
            'tax_total' => '0.0000',
            'discount_total' => '0.0000',
            'grand_total' => '500.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '500.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        $this->createReceipt('RCP-SEARCH-101', '500.0000', 'cash');

        Livewire::test(GlobalSearch::class)
            ->set('query', 'Jane')
            ->assertSee('Jane Monitoring Doe')
            ->set('query', 'INV-SEARCH-101')
            ->assertSee('INV-SEARCH-101')
            ->set('query', 'SUB-999-001')
            ->assertSee('SUB-999-001')
            ->set('query', 'RCP-SEARCH-101')
            ->assertSee('RCP-SEARCH-101');
    }

    /**
     * Test Customer Details Financial Snapshot & Ledger Mutation History.
     */
    public function test_customer_detail_renders_financial_snapshot_and_ledger_history(): void
    {
        // 1. Setup Wallet Balance
        $wallet = Wallet::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => Customer::class,
            'owner_id' => $this->customer->id,
            'currency' => 'BDT',
            'current_balance' => '750.0000',
        ]);

        // 2. Setup Ledger entry
        LedgerEntry::create([
            'tenant_id' => $this->tenant->id,
            'wallet_id' => $wallet->id,
            'transaction_type' => \App\Modules\Billing\Enums\TransactionType::MANUAL_PAYMENT_RECEIPT,
            'direction' => \App\Modules\Billing\Enums\TransactionDirection::CREDIT,
            'amount' => '750.0000',
            'balance_before' => '0.0000',
            'balance_after' => '750.0000',
            'notes' => 'Test deposit',
        ]);

        Livewire::test(CustomerDetail::class, ['id' => $this->customer->id])
            ->assertSee('Financial Snapshot')
            ->assertSee('Wallet Ledger Mutation History')
            ->assertSee('deposit')
            ->assertSee('credit')
            ->assertSee('750.00 BDT');
    }

    /**
     * Verify that events are broadcasted on tenant.dashboard channel.
     */
    public function test_reverb_events_broadcast_payment_suspension_reversal_gateway(): void
    {
        Event::fake();

        // 1. Broadcast PaymentReceived
        event(new \App\Modules\Tenant\Events\PaymentReceived('RCP-001', '500.00', 'Jane Doe', now()->toDateTimeString()));
        Event::assertDispatched(\App\Modules\Tenant\Events\PaymentReceived::class);

        // 2. Broadcast SuspensionTriggered
        event(new \App\Modules\Tenant\Events\SuspensionTriggered('SUB-001', 'Jane Doe', 'Overdue dues', now()->toDateTimeString()));
        Event::assertDispatched(\App\Modules\Tenant\Events\SuspensionTriggered::class);

        // 3. Broadcast ReversalCompleted
        event(new \App\Modules\Tenant\Events\ReversalCompleted('RCP-001', '500.00', 'Jane Doe', 'Mistake', now()->toDateTimeString()));
        Event::assertDispatched(\App\Modules\Tenant\Events\ReversalCompleted::class);

        // 4. Broadcast GatewayStatusUpdated
        event(new \App\Modules\Tenant\Events\GatewayStatusUpdated('ATT-001', 'bkash', '500.00', 'verified', now()->toDateTimeString()));
        Event::assertDispatched(\App\Modules\Tenant\Events\GatewayStatusUpdated::class);
    }
}
