<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerAddress;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Models\ServicePlan;
use App\Modules\Billing\Models\TaxRule;
use App\Modules\Customer\Http\Livewire\CustomerIndex;
use App\Modules\Customer\Http\Livewire\CustomerDetail;
use App\Modules\Customer\Http\Livewire\ServicePlanCrud;
use App\Modules\Customer\Http\Livewire\SubscriptionIndex;
use App\Modules\Billing\Http\Livewire\InvoiceIndex;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Modules\Billing\Http\Livewire\PaymentIntake;
use App\Modules\Billing\Http\Livewire\WalletIndex;
use App\Modules\Billing\Http\Livewire\AllocationIndex;
use App\Modules\Billing\Http\Livewire\GatewayAttemptsIndex;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\GatewayPaymentAttempt;

class AdminDashboardMvpTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenant;
    protected TenantManager $tenantManager;

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
    }

    /**
     * Test Service Plan Catalog CRUD operations.
     */
    public function test_service_plan_crud_operations(): void
    {
        // 1. Verify mounting
        $component = Livewire::test(ServicePlanCrud::class)
            ->assertSee('Configure service plans')
            ->assertSee('Create Service Plan');

        // 2. Create service plan
        $component->set('plan_code', 'PLAN-20M')
            ->set('name', '20 Mbps Gold')
            ->set('monthly_price', '800.0000')
            ->set('billing_cycle', 'monthly')
            ->set('download_speed', '20 Mbps')
            ->set('upload_speed', '10 Mbps')
            ->set('is_active', true)
            ->call('createPlan')
            ->assertHasNoErrors()
            ->assertSee('20 Mbps Gold');

        $this->assertDatabaseHas('service_plans', [
            'tenant_id' => $this->tenant->id,
            'plan_code' => 'PLAN-20M',
            'name' => '20 Mbps Gold',
            'monthly_price' => '800.0000',
        ]);
    }

    /**
     * Test Customer creation and profile updates.
     */
    public function test_customer_crud_operations(): void
    {
        Livewire::test(CustomerIndex::class)
            ->assertSee('Manage active ISP subscribers')
            ->set('customer_code', 'CUST-99999')
            ->set('full_name', 'Mashiur Rahman')
            ->set('phone', '+8801712345678')
            ->set('email', 'mashiur@gmail.com')
            ->set('address', 'House 42, Road 12, Dhanmondi, Dhaka')
            ->set('status', 'active')
            ->set('type', 'residential')
            ->call('createCustomer')
            ->assertHasNoErrors()
            ->assertSee('Mashiur Rahman');

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant->id,
            'customer_code' => 'CUST-99999',
            'full_name' => 'Mashiur Rahman',
            'phone' => '+8801712345678',
        ]);

        $customer = Customer::where('customer_code', 'CUST-99999')->firstOrFail();
        $this->assertDatabaseHas('customer_addresses', [
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'address_line_1' => 'House 42, Road 12, Dhanmondi, Dhaka',
        ]);
    }

    /**
     * Test Subscription assignment and invoice generation.
     */
    public function test_subscription_assignment_and_billing_generation(): void
    {
        // 1. Set up plan and customer
        $plan = ServicePlan::create([
            'tenant_id' => $this->tenant->id,
            'plan_code' => 'PLAN-50M',
            'name' => '50 Mbps Platinum',
            'monthly_price' => '1500.0000',
            'billing_cycle' => 'monthly',
            'download_speed' => '50 Mbps',
            'upload_speed' => '25 Mbps',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'customer_code' => 'CUST-77777',
            'type' => \App\Modules\Customer\Enums\CustomerType::RESIDENTIAL,
            'full_name' => 'Jamil Ahmed',
            'phone' => '+8801811122233',
            'status' => \App\Modules\Customer\Enums\CustomerStatus::ACTIVE,
        ]);

        CustomerAddress::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'type' => \App\Modules\Customer\Enums\AddressType::BOTH,
            'label' => 'Primary',
            'address_line_1' => 'Mirpur-10, Dhaka',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'is_default_billing' => true,
            'is_default_installation' => true,
        ]);

        // 2. Assign plan via CustomerDetail component
        $detailComponent = Livewire::test(CustomerDetail::class, ['id' => $customer->id])
            ->assertSee('Jamil Ahmed')
            ->set('selectedPlanId', $plan->id)
            ->set('billingMode', 'prepaid')
            ->set('billingCycleType', 'anniversary')
            ->set('nextBillingDate', now()->toDateString())
            ->call('assignPlan')
            ->assertHasNoErrors()
            ->assertSee('50 Mbps Platinum');

        $subscription = CustomerServiceSubscription::where('customer_id', $customer->id)->firstOrFail();
        $this->assertEquals($plan->id, $subscription->service_plan_id);

        // 3. Trigger invoice generation
        $detailComponent->call('generateInvoice', $subscription->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'customer_service_subscription_id' => $subscription->id,
            'grand_total' => '1500.0000',
            'status' => 'issued',
        ]);
    }

    /**
     * Test Payment Intake processing.
     */
    public function test_payment_intake_creates_receipt_and_allocates(): void
    {
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'customer_code' => 'CUST-33333',
            'type' => \App\Modules\Customer\Enums\CustomerType::RESIDENTIAL,
            'full_name' => 'Fahim Faisal',
            'phone' => '+8801999888777',
            'status' => \App\Modules\Customer\Enums\CustomerStatus::ACTIVE,
        ]);

        // Create an outstanding invoice
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2026-000001',
            'invoice_type' => \App\Modules\Billing\Enums\InvoiceType::SUBSCRIPTION,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'subtotal' => '1000.0000',
            'grand_total' => '1000.0000',
            'amount_due' => '1000.0000',
            'amount_paid' => '0.0000',
            'status' => \App\Modules\Billing\Enums\InvoiceStatus::ISSUED->value,
            'billing_period_start' => now(),
            'billing_period_end' => now()->addMonth(),
        ]);

        // Verify component mounts and displays form
        $component = Livewire::test(PaymentIntake::class)
            ->assertSee('Ingest manual payments')
            ->assertSee('Post Payment Receipt')
            ->set('selectedCustomerId', $customer->id)
            ->set('amount', '1000.0000')
            ->set('paymentMethod', 'cash')
            ->set('reference', 'TXN-998877')
            ->set('notes', 'Manual cash payment receipt')
            ->set('autoAllocate', true)
            ->call('submitPayment')
            ->assertHasNoErrors();

        // Verify receipt is saved
        $this->assertDatabaseHas('payment_receipts', [
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'amount' => '1000.0000',
            'reference' => 'TXN-998877',
        ]);

        // Verify wallet is initialized and balance is 0.0000 (after allocation debited it)
        $wallet = Wallet::where('owner_id', $customer->id)->firstOrFail();
        $this->assertEquals('0.0000', bcadd($wallet->current_balance, '0', 4));

        // Verify allocation updated the invoice status to PAID
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => \App\Modules\Billing\Enums\InvoiceStatus::PAID->value,
            'amount_due' => '0.0000',
            'amount_paid' => '1000.0000',
        ]);

        // Verify allocation row was created
        $this->assertDatabaseHas('payment_allocations', [
            'tenant_id' => $this->tenant->id,
            'invoice_id' => $invoice->id,
            'wallet_id' => $wallet->id,
            'allocation_amount' => '1000.0000',
        ]);
    }

    /**
     * Test Wallet view and logs.
     */
    public function test_wallet_index_displays_balances(): void
    {
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'customer_code' => 'CUST-44444',
            'type' => \App\Modules\Customer\Enums\CustomerType::RESIDENTIAL,
            'full_name' => 'Sakib Al Hasan',
            'phone' => '+8801555555555',
            'status' => \App\Modules\Customer\Enums\CustomerStatus::ACTIVE,
        ]);

        $wallet = Wallet::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => Customer::class,
            'owner_id' => $customer->id,
            'currency' => 'BDT',
            'current_balance' => '500.0000',
            'active' => true,
        ]);

        Livewire::test(WalletIndex::class)
            ->assertSee('Sakib Al Hasan')
            ->assertSee('500.00')
            ->call('showLedger', $wallet->id)
            ->assertSee('Wallet Ledger');
    }

    /**
     * Test Allocation log index.
     */
    public function test_allocation_index_displays_allocations(): void
    {
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'customer_code' => 'CUST-55555',
            'type' => \App\Modules\Customer\Enums\CustomerType::RESIDENTIAL,
            'full_name' => 'Tamim Iqbal',
            'phone' => '+8801666666666',
            'status' => \App\Modules\Customer\Enums\CustomerStatus::ACTIVE,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2026-999',
            'invoice_type' => \App\Modules\Billing\Enums\InvoiceType::SUBSCRIPTION,
            'issue_date' => now(),
            'due_date' => now(),
            'subtotal' => '500.0000',
            'grand_total' => '500.0000',
            'amount_due' => '0.0000',
            'amount_paid' => '500.0000',
            'status' => \App\Modules\Billing\Enums\InvoiceStatus::PAID->value,
            'billing_period_start' => now(),
            'billing_period_end' => now()->addMonth(),
        ]);

        $wallet = Wallet::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => Customer::class,
            'owner_id' => $customer->id,
            'currency' => 'BDT',
            'current_balance' => '0.0000',
            'active' => true,
        ]);

        $ledger = \App\Modules\Billing\Models\LedgerEntry::create([
            'tenant_id' => $this->tenant->id,
            'wallet_id' => $wallet->id,
            'transaction_type' => \App\Modules\Billing\Enums\TransactionType::PAYMENT_ALLOCATION->value,
            'direction' => \App\Modules\Billing\Enums\TransactionDirection::DEBIT->value,
            'amount' => '500.0000',
            'balance_before' => '500.0000',
            'balance_after' => '0.0000',
        ]);

        \App\Modules\Billing\Models\PaymentAllocation::create([
            'tenant_id' => $this->tenant->id,
            'invoice_id' => $invoice->id,
            'wallet_id' => $wallet->id,
            'ledger_entry_id' => $ledger->id,
            'allocation_amount' => '500.0000',
            'allocation_order' => 1,
        ]);

        Livewire::test(AllocationIndex::class)
            ->assertSee('Tamim Iqbal')
            ->assertSee('INV-2026-999')
            ->assertSee('500.00');
    }

    /**
     * Test Gateway attempts audit logs.
     */
    public function test_gateway_attempts_index_displays_attempts(): void
    {
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'customer_code' => 'CUST-66666',
            'type' => \App\Modules\Customer\Enums\CustomerType::RESIDENTIAL,
            'full_name' => 'Mushfiqur Rahim',
            'phone' => '+8801333333333',
            'status' => \App\Modules\Customer\Enums\CustomerStatus::ACTIVE,
        ]);

        GatewayPaymentAttempt::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'gateway' => 'bkash_gateway',
            'attempt_reference' => 'BK-REF-889900',
            'requested_amount' => '1200.0000',
            'currency' => 'BDT',
            'status' => 'success',
            'gateway_payload' => ['transaction_id' => 'TRX7766'],
        ]);

        Livewire::test(GatewayAttemptsIndex::class)
            ->assertSee('Mushfiqur Rahim')
            ->assertSee('bkash_gateway')
            ->assertSee('BK-REF-889900')
            ->call('showPayload', 1)
            ->assertSee('Gateway Payload Metadata')
            ->assertSee('TRX7766');
    }
}
