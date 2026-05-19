<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\PaymentAllocation;
use App\Modules\Billing\Models\LedgerEntry;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Billing\Enums\TransactionType;
use App\Modules\Billing\Enums\TransactionDirection;
use App\Modules\Billing\Services\WalletService;
use App\Modules\Billing\Services\PaymentAllocationService;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PaymentAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected Customer $customerA;
    protected Customer $customerB;
    protected WalletService $walletService;
    protected PaymentAllocationService $allocationService;
    protected Wallet $walletA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Premium Plan',
            'slug' => 'premium',
            'price_monthly' => 50.00,
            'price_yearly' => 500.00,
        ]);

        $this->tenantA = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'slug' => 'tenant-a',
            'name' => 'Tenant A',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'slug' => 'tenant-b',
            'name' => 'Tenant B',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $tenantManager = app(TenantManager::class);
        $customerService = app(\App\Modules\Customer\Services\CustomerService::class);

        // Setup Tenant A Customer & Wallet
        $tenantManager->setTenant($this->tenantA);
        $this->customerA = $customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'John Tenant A',
            'phone' => '01700000001',
            'email' => 'john@tenant-a.com',
        ]);

        $this->walletService = app(WalletService::class);
        $this->allocationService = app(PaymentAllocationService::class);

        $this->walletA = $this->walletService->createWallet(
            $this->tenantA->id,
            Customer::class,
            $this->customerA->id,
            'BDT'
        );

        // Setup Tenant B Customer & Wallet
        $tenantManager->setTenant($this->tenantB);
        $this->customerB = $customerService->createCustomer($this->tenantB->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Jane Tenant B',
            'phone' => '01700000002',
            'email' => 'jane@tenant-b.com',
        ]);

        $this->walletService->createWallet(
            $this->tenantB->id,
            Customer::class,
            $this->customerB->id,
            'BDT'
        );
    }

    /**
     * Test full settlement of a single issued invoice.
     */
    public function test_full_invoice_settlement(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // Credit the wallet
        $this->walletService->mutateBalance(
            $this->tenantA->id,
            $this->walletA->id,
            TransactionDirection::CREDIT->value,
            '1000.0000',
            TransactionType::MANUAL_CREDIT->value
        );

        // Create an invoice
        $invoice = Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-001',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'currency' => 'BDT',
            'subtotal' => '800.0000',
            'tax_total' => '0.0000',
            'discount_total' => '0.0000',
            'grand_total' => '800.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '800.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        // Allocate payment
        $allocations = $this->allocationService->allocateFromWallet(
            $this->tenantA->id,
            $this->walletA->id,
            '800.0000'
        );

        $this->assertCount(1, $allocations);
        $allocation = $allocations->first();
        $this->assertEquals('800.0000', $allocation->allocation_amount);
        $this->assertEquals(1, $allocation->allocation_order);

        // Verify Invoice state
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::PAID, $invoice->status);
        $this->assertEquals('800.0000', $invoice->amount_paid);
        $this->assertEquals('0.0000', $invoice->amount_due);

        // Verify Wallet balance
        $this->walletA->refresh();
        $this->assertEquals('200.0000', $this->walletA->current_balance);

        // Verify Ledger entry exists
        $ledger = LedgerEntry::latest('id')->first();
        $this->assertEquals(TransactionType::PAYMENT_ALLOCATION, $ledger->transaction_type);
        $this->assertEquals(TransactionDirection::DEBIT, $ledger->direction);
        $this->assertEquals('800.0000', $ledger->amount);
    }

    /**
     * Test partial settlement of a single issued invoice.
     */
    public function test_partial_invoice_settlement(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // Credit the wallet
        $this->walletService->mutateBalance(
            $this->tenantA->id,
            $this->walletA->id,
            TransactionDirection::CREDIT->value,
            '500.0000',
            TransactionType::MANUAL_CREDIT->value
        );

        // Create an invoice
        $invoice = Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-002',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'currency' => 'BDT',
            'subtotal' => '800.0000',
            'tax_total' => '0.0000',
            'discount_total' => '0.0000',
            'grand_total' => '800.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '800.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        // Allocate payment
        $allocations = $this->allocationService->allocateFromWallet(
            $this->tenantA->id,
            $this->walletA->id,
            '500.0000'
        );

        $this->assertCount(1, $allocations);
        $allocation = $allocations->first();
        $this->assertEquals('500.0000', $allocation->allocation_amount);

        // Verify Invoice state
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::PARTIALLY_PAID, $invoice->status);
        $this->assertEquals('500.0000', $invoice->amount_paid);
        $this->assertEquals('300.0000', $invoice->amount_due);

        // Verify Wallet balance
        $this->walletA->refresh();
        $this->assertEquals('0.0000', $this->walletA->current_balance);
    }

    /**
     * Test multi-invoice FIFO sorting and excess preservation.
     */
    public function test_multi_invoice_fifo_allocation(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // Credit the wallet
        $this->walletService->mutateBalance(
            $this->tenantA->id,
            $this->walletA->id,
            TransactionDirection::CREDIT->value,
            '2500.0000',
            TransactionType::MANUAL_CREDIT->value
        );

        // Create 3 invoices with different due dates and numbers
        $inv1 = Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-001',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'currency' => 'BDT',
            'subtotal' => '500.0000',
            'grand_total' => '500.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '500.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        // Same due date as inv3, alphabetical tie-break should process INV-002 first
        $inv2 = Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-002',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(10),
            'currency' => 'BDT',
            'subtotal' => '1200.0000',
            'grand_total' => '1200.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '1200.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        $inv3 = Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-003',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(10),
            'currency' => 'BDT',
            'subtotal' => '900.0000',
            'grand_total' => '900.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '900.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        // Allocate 2000.0000 BDT
        $allocations = $this->allocationService->allocateFromWallet(
            $this->tenantA->id,
            $this->walletA->id,
            '2000.0000'
        );

        $this->assertCount(3, $allocations);

        // Sorted allocations:
        // Order 1: INV-001 (500) -> Paid
        // Order 2: INV-002 (1200) -> Paid
        // Order 3: INV-003 (300 out of 900) -> Partially Paid
        $this->assertEquals($inv1->id, $allocations[0]->invoice_id);
        $this->assertEquals('500.0000', $allocations[0]->allocation_amount);

        $this->assertEquals($inv2->id, $allocations[1]->invoice_id);
        $this->assertEquals('1200.0000', $allocations[1]->allocation_amount);

        $this->assertEquals($inv3->id, $allocations[2]->invoice_id);
        $this->assertEquals('300.0000', $allocations[2]->allocation_amount);

        // Verify Invoices states
        $inv1->refresh();
        $this->assertEquals(InvoiceStatus::PAID, $inv1->status);

        $inv2->refresh();
        $this->assertEquals(InvoiceStatus::PAID, $inv2->status);

        $inv3->refresh();
        $this->assertEquals(InvoiceStatus::PARTIALLY_PAID, $inv3->status);
        $this->assertEquals('300.0000', $inv3->amount_paid);
        $this->assertEquals('600.0000', $inv3->amount_due);

        // Verify Wallet balance:
        // Started with 2500, allocated 2000, 500 remaining.
        $this->walletA->refresh();
        $this->assertEquals('500.0000', $this->walletA->current_balance);
    }

    /**
     * Test idempotency protection.
     */
    public function test_idempotency_guard(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // Credit the wallet
        $this->walletService->mutateBalance(
            $this->tenantA->id,
            $this->walletA->id,
            TransactionDirection::CREDIT->value,
            '1000.0000',
            TransactionType::MANUAL_CREDIT->value
        );

        // Create invoice
        Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-001',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'currency' => 'BDT',
            'subtotal' => '800.0000',
            'grand_total' => '800.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '800.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        $options = ['idempotency_key' => 'alloc-idemp-unique-999'];

        $res1 = $this->allocationService->allocateFromWallet(
            $this->tenantA->id,
            $this->walletA->id,
            '800.0000',
            $options
        );

        // Try submitting same transaction
        $res2 = $this->allocationService->allocateFromWallet(
            $this->tenantA->id,
            $this->walletA->id,
            '800.0000',
            $options
        );

        // Asserts same allocations returned
        $this->assertEquals($res1->pluck('id'), $res2->pluck('id'));

        $this->walletA->refresh();
        $this->assertEquals('200.0000', $this->walletA->current_balance);
        $this->assertCount(1, PaymentAllocation::all());
    }

    /**
     * Test inactive wallet rejection.
     */
    public function test_inactive_wallet_rejection(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $this->walletA->active = false;
        $this->walletA->save();

        Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-001',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'currency' => 'BDT',
            'subtotal' => '800.0000',
            'grand_total' => '800.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '800.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        $this->expectException(ValidationException::class);
        $this->allocationService->allocateFromWallet(
            $this->tenantA->id,
            $this->walletA->id,
            '800.0000'
        );
    }

    /**
     * Test insufficient wallet balance rejection.
     */
    public function test_insufficient_balance_rejection(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-001',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'currency' => 'BDT',
            'subtotal' => '800.0000',
            'grand_total' => '800.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '800.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        $this->expectException(ValidationException::class);
        $this->allocationService->allocateFromWallet(
            $this->tenantA->id,
            $this->walletA->id,
            '800.0000'
        );
    }

    /**
     * Test invalid amount validation (non-positive amounts).
     */
    public function test_invalid_amount_rejection(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $this->expectException(ValidationException::class);
        $this->allocationService->allocateFromWallet(
            $this->tenantA->id,
            $this->walletA->id,
            '-50.0000'
        );
    }

    /**
     * Test that allocations to non-eligible invoice statuses are blocked.
     */
    public function test_invalid_status_rejection(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // Credit the wallet
        $this->walletService->mutateBalance(
            $this->tenantA->id,
            $this->walletA->id,
            TransactionDirection::CREDIT->value,
            '1000.0000',
            TransactionType::MANUAL_CREDIT->value
        );

        // Draft invoice
        Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-DRAFT',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'currency' => 'BDT',
            'subtotal' => '800.0000',
            'grand_total' => '800.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '0.0000', // Draft has no amount due
            'status' => InvoiceStatus::DRAFT->value,
        ]);

        try {
            $this->allocationService->allocateFromWallet(
                $this->tenantA->id,
                $this->walletA->id,
                '800.0000'
            );
            $this->fail('Allocated to a draft invoice.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('invoices', $e->errors());
        }
    }

    /**
     * Test restrictive delete database integrity.
     */
    public function test_restrictive_delete_integrity(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // Credit the wallet
        $this->walletService->mutateBalance(
            $this->tenantA->id,
            $this->walletA->id,
            TransactionDirection::CREDIT->value,
            '1000.0000',
            TransactionType::MANUAL_CREDIT->value
        );

        // Create invoice
        $invoice = Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-001',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'currency' => 'BDT',
            'subtotal' => '800.0000',
            'grand_total' => '800.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '800.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        $this->allocationService->allocateFromWallet(
            $this->tenantA->id,
            $this->walletA->id,
            '800.0000'
        );

        // Try to delete the invoice (should fail due to restrict FK constraint)
        $this->expectException(QueryException::class);
        $invoice->delete();
    }

    /**
     * Test tenant isolation boundary leak protection.
     */
    public function test_tenant_isolation_leak_protection(): void
    {
        $tenantManager = app(TenantManager::class);

        // Create Invoice under Tenant A
        $tenantManager->setTenant($this->tenantA);
        Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-001',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'currency' => 'BDT',
            'subtotal' => '800.0000',
            'grand_total' => '800.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '800.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        // Attempting to allocate Tenant A's invoice while acting under Tenant B must fail
        $tenantManager->setTenant($this->tenantB);
        $walletB = Wallet::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantB->id)
            ->first();

        // Mutate Tenant B wallet balance
        $this->walletService->mutateBalance(
            $this->tenantB->id,
            $walletB->id,
            TransactionDirection::CREDIT->value,
            '1000.0000',
            TransactionType::MANUAL_CREDIT->value
        );

        try {
            $this->allocationService->allocateFromWallet(
                $this->tenantB->id,
                $walletB->id,
                '800.0000'
            );
            $this->fail('Allocated Tenant B wallet to Tenant A invoices.');
        } catch (ValidationException $e) {
            // Fails because no eligible outstanding invoices exist for Tenant B's customer
            $this->assertArrayHasKey('invoices', $e->errors());
        }
    }

    /**
     * Test RBAC Policy controls on PaymentAllocation.
     */
    public function test_rbac_policy_enforcement(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $this->walletService->mutateBalance(
            $this->tenantA->id,
            $this->walletA->id,
            TransactionDirection::CREDIT->value,
            '1000.0000',
            TransactionType::MANUAL_CREDIT->value
        );

        Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-001',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'currency' => 'BDT',
            'subtotal' => '800.0000',
            'grand_total' => '800.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '800.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        $allocations = $this->allocationService->allocateFromWallet(
            $this->tenantA->id,
            $this->walletA->id,
            '800.0000'
        );
        $allocation = $allocations->first();

        // Create Permissions
        $viewPermission = Permission::create([
            'module' => 'billing',
            'action' => 'view_allocation',
            'slug' => 'billing.allocation.view',
        ]);

        $managePermission = Permission::create([
            'module' => 'billing',
            'action' => 'manage_allocation',
            'slug' => 'billing.allocation.manage',
        ]);

        // Scenario 1: Staff User with view-only permission
        $viewRole = Role::create(['name' => 'Allocation Viewer', 'slug' => 'alloc-viewer']);
        $viewRole->permissions()->attach($viewPermission);

        $viewerStaff = StaffUser::create([
            'name' => 'Alloc Viewer Staff',
            'email' => 'viewer@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
            'tenant_id' => $this->tenantA->id,
        ]);
        $viewerStaff->roles()->attach($viewRole);

        $this->actingAs($viewerStaff, 'sanctum');

        $this->assertTrue(Gate::allows('view', $allocation));
        $this->assertFalse(Gate::allows('create', PaymentAllocation::class));

        // Scenario 2: Staff User with manage permission
        $managerRole = Role::create(['name' => 'Allocation Manager', 'slug' => 'alloc-manager']);
        $managerRole->permissions()->attach($managePermission);

        $managerStaff = StaffUser::create([
            'name' => 'Alloc Manager Staff',
            'email' => 'manager@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
            'tenant_id' => $this->tenantA->id,
        ]);
        $managerStaff->roles()->attach($managerRole);

        $this->actingAs($managerStaff, 'sanctum');

        $this->assertTrue(Gate::allows('create', PaymentAllocation::class));

        // Scenario 3: Tenant Isolation Checks inside policies
        $tenantBStaff = StaffUser::create([
            'name' => 'Tenant B Staff',
            'email' => 'staff@tenant-b.com',
            'password' => 'secret123',
            'status' => 'active',
            'tenant_id' => $this->tenantB->id,
        ]);
        $tenantBStaff->roles()->attach($managerRole);

        $this->actingAs($tenantBStaff, 'sanctum');

        $this->assertFalse(Gate::allows('view', $allocation));
    }
}
