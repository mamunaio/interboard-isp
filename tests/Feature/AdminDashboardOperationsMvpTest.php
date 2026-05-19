<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Core\Settings\SettingsRegistry;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerAddress;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Models\ServicePlan;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\LedgerEntry;
use App\Modules\Billing\Models\SuspensionEvent;
use App\Modules\Billing\Models\ReversalEvent;
use App\Modules\Billing\Models\PaymentAllocation;
use App\Modules\Billing\Models\PaymentAllocationReversal;
use App\Modules\Billing\Http\Livewire\SubscriptionActions;
use App\Modules\Billing\Http\Livewire\ReversalsIndex;
use App\Modules\Customer\Http\Livewire\CustomerDetail;
use App\Modules\Billing\Http\Livewire\InvoiceIndex;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminDashboardOperationsMvpTest extends TestCase
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

        // Settings
        $settings = app(SettingsRegistry::class);
        $settings->set('reconnection_fee_enabled', true);
        $settings->set('reconnection_fee_amount', '500.0000');

        // Create Customer
        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'customer_code' => 'CUST-001',
            'full_name' => 'John Doe Operations',
            'email' => 'john.ops@example.com',
            'phone' => '01700000001',
            'status' => 'active',
            'type' => \App\Modules\Customer\Enums\CustomerType::RESIDENTIAL,
        ]);

        // Address
        CustomerAddress::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'type' => \App\Modules\Customer\Enums\AddressType::BOTH,
            'label' => 'Primary',
            'address_line_1' => 'Dhaka, Bangladesh',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
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
            'subscription_code' => 'SUB-2026-000001',
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
     * Test SubscriptionActions component renders, can suspend, and reactivate.
     */
    public function test_subscription_actions_flow(): void
    {
        // 0. Setup an overdue unpaid invoice exceeding grace days
        $overdueInvoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'customer_service_subscription_id' => $this->subscription->id,
            'invoice_number' => 'INV-OVERDUE-01',
            'invoice_type' => \App\Modules\Billing\Enums\InvoiceType::SUBSCRIPTION,
            'issue_date' => now()->subDays(10),
            'due_date' => now()->subDays(5),
            'subtotal' => '500.0000',
            'tax_total' => '0.0000',
            'discount_total' => '0.0000',
            'grand_total' => '500.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '500.0000',
            'status' => \App\Modules\Billing\Enums\InvoiceStatus::ISSUED,
        ]);

        // 1. Mount component
        $component = Livewire::test(SubscriptionActions::class)
            ->assertSee('Subscription')
            ->assertSee('Actions')
            ->assertSee('SUB-2026-000001')
            ->assertSee('John Doe Operations');

        // 2. Open Suspend modal and suspend
        $component->call('openSuspendModal', $this->subscription->id)
            ->assertSet('selectedSubscriptionId', $this->subscription->id)
            ->set('suspensionReason', 'Temporary holiday suspend')
            ->call('suspend')
            ->assertHasNoErrors()
            ->assertSet('isSuspendModalOpen', false);

        // Verify state is suspended
        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStatus::SUSPENDED, $this->subscription->status);

        // Check audit event
        $this->assertDatabaseHas('suspension_events', [
            'tenant_id' => $this->tenant->id,
            'customer_service_subscription_id' => $this->subscription->id,
            'action_type' => 'suspended',
            'reason' => 'Temporary holiday suspend',
        ]);

        // Pay the overdue invoice to allow reactivation
        $overdueInvoice->update([
            'amount_due' => '0.0000',
            'amount_paid' => '500.0000',
            'status' => \App\Modules\Billing\Enums\InvoiceStatus::PAID,
        ]);

        // 3. Reactivate subscription
        $component->call('openReactivateModal', $this->subscription->id)
            ->assertSet('selectedSubscriptionId', $this->subscription->id)
            ->set('reactivationReason', 'Holiday over, reactivated')
            ->set('reconnectionFeeEnabled', true)
            ->set('reconnectionFeeAmount', '500.0000')
            ->call('reactivate')
            ->assertHasNoErrors()
            ->assertSet('isReactivateModalOpen', false);

        // Verify state is active
        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $this->subscription->status);

        // Check reconnection fee invoice generated
        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'invoice_type' => \App\Modules\Billing\Enums\InvoiceType::RECONNECTION_FEE->value,
            'grand_total' => '500.0000',
        ]);

        // Check audit event
        $this->assertDatabaseHas('suspension_events', [
            'tenant_id' => $this->tenant->id,
            'customer_service_subscription_id' => $this->subscription->id,
            'action_type' => 'reactivated',
            'reason' => 'Holiday over, reactivated',
        ]);
    }

    /**
     * Test ReversalsIndex component renders, can reverse receipt, and post adjustments.
     */
    public function test_reversals_and_adjustments_flow(): void
    {
        // Setup initial invoice and payment receipt
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-2026-0001',
            'status' => \App\Modules\Billing\Enums\InvoiceStatus::ISSUED,
            'invoice_type' => \App\Modules\Billing\Enums\InvoiceType::SUBSCRIPTION,
            'subtotal' => '1000.0000',
            'tax_total' => '0.0000',
            'discount_total' => '0.0000',
            'grand_total' => '1000.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '1000.0000',
            'issue_date' => now(),
            'due_date' => now()->addDays(15),
        ]);

        $receipt = $this->createReceipt('RCPT-0001', '1000.0000', 'cash');

        $wallet = Wallet::where('tenant_id', $this->tenant->id)
            ->where('owner_type', Customer::class)
            ->where('owner_id', $this->customer->id)
            ->first();

        $allocation = PaymentAllocation::create([
            'tenant_id' => $this->tenant->id,
            'payment_receipt_id' => $receipt->id,
            'invoice_id' => $invoice->id,
            'wallet_id' => $wallet->id,
            'allocation_amount' => '1000.0000',
            'allocation_order' => 1,
        ]);

        // 1. Mount component
        $component = Livewire::test(ReversalsIndex::class)
            ->assertSee('Reversals')
            ->assertSee('Adjustments')
            ->assertSee('RCPT-0001')
            ->assertSee('John Doe Operations');

        // 2. Reverse receipt
        $component->call('openReverseModal', $receipt->id)
            ->set('reversalReason', 'Customer request reversal')
            ->call('reverseReceipt')
            ->assertHasNoErrors();

        // Verify allocation reversal

        $this->assertDatabaseHas('payment_allocation_reversals', [
            'tenant_id' => $this->tenant->id,
            'payment_allocation_id' => $allocation->id,
            'amount' => '1000.0000',
        ]);

        // Verify audit event
        $this->assertDatabaseHas('reversal_events', [
            'tenant_id' => $this->tenant->id,
            'reversal_type' => \App\Modules\Billing\Enums\TransactionType::PAYMENT_RECEIPT_REVERSAL->value,
            'original_entity_type' => \App\Modules\Billing\Models\PaymentReceipt::class,
            'original_entity_id' => $receipt->id,
        ]);

        // 3. Post manual adjustment
        $component->set('adjCustomerId', $this->customer->id)
            ->set('adjType', 'credit_adjustment')
            ->set('adjAmount', '250.0000')
            ->set('adjReason', 'Goodwill credit adjustment')
            ->call('submitAdjustment')
            ->assertHasNoErrors();

        // Check wallet balance
        $wallet = Wallet::where('tenant_id', $this->tenant->id)
            ->where('owner_type', Customer::class)
            ->where('owner_id', $this->customer->id)
            ->first();
        
        $this->assertNotNull($wallet);
        $this->assertEquals('250.0000', $wallet->current_balance);

        // Check audit event
        $this->assertDatabaseHas('reversal_events', [
            'tenant_id' => $this->tenant->id,
            'reversal_type' => 'credit_adjustment',
            'amount' => '250.0000',
            'reason' => 'Goodwill credit adjustment',
        ]);
    }

    /**
     * Test enhancements on Customer Detail view.
     */
    public function test_customer_detail_enhancements(): void
    {
        // Setup wallet & recent receipt
        Wallet::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => Customer::class,
            'owner_id' => $this->customer->id,
            'currency' => 'BDT',
            'current_balance' => '75.5000',
        ]);

        $this->createReceipt('RCPT-9999', '300.0000', 'cash');

        Livewire::test(CustomerDetail::class, ['id' => $this->customer->id])
            ->assertSee('Wallet Balance')
            ->assertSee('75.50')
            ->assertSee('RCPT-9999')
            ->assertSee('300.00');
    }

    /**
     * Test enhancements on Invoice Detail view.
     */
    public function test_invoice_detail_enhancements(): void
    {
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-TEST-0002',
            'status' => \App\Modules\Billing\Enums\InvoiceStatus::ISSUED,
            'invoice_type' => \App\Modules\Billing\Enums\InvoiceType::SUBSCRIPTION,
            'subtotal' => '800.0000',
            'tax_total' => '0.0000',
            'discount_total' => '0.0000',
            'grand_total' => '800.0000',
            'amount_paid' => '300.0000',
            'amount_due' => '500.0000',
            'issue_date' => now(),
            'due_date' => now()->addDays(15),
        ]);

        $receipt = $this->createReceipt('RCPT-0022', '300.0000', 'bkash_gateway');

        PaymentAllocation::create([
            'tenant_id' => $this->tenant->id,
            'payment_receipt_id' => $receipt->id,
            'invoice_id' => $invoice->id,
            'allocation_amount' => '300.0000',
            'allocation_order' => 1,
        ]);

        Livewire::test(InvoiceIndex::class)
            ->call('showInvoice', $invoice->id)
            ->assertSee('Outstanding Balance')
            ->assertSee('500.00')
            ->assertSee('Amount Paid')
            ->assertSee('300.00')
            ->assertSee('RCPT-0022')
            ->assertSee('bkash_gateway');
    }
}
