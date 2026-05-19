<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\LedgerEntry;
use App\Modules\Billing\Enums\TransactionType;
use App\Modules\Billing\Enums\TransactionDirection;
use App\Modules\Billing\Services\WalletService;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class WalletLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected Customer $customerA;
    protected Customer $customerB;
    protected WalletService $walletService;

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

        // Create Customer for Tenant A
        $tenantManager->setTenant($this->tenantA);
        $this->customerA = $customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'John Tenant A',
            'phone' => '01700000001',
            'email' => 'john@tenant-a.com',
        ]);

        // Create Customer for Tenant B
        $tenantManager->setTenant($this->tenantB);
        $this->customerB = $customerService->createCustomer($this->tenantB->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Jane Tenant B',
            'phone' => '01700000002',
            'email' => 'jane@tenant-b.com',
        ]);

        $this->walletService = app(WalletService::class);
    }

    /**
     * Test Wallet CRUD and unique constraints.
     */
    public function test_wallet_creation_and_uniqueness_checks(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // 1. Successful wallet creation
        $wallet = $this->walletService->createWallet(
            $this->tenantA->id,
            Customer::class,
            $this->customerA->id,
            'BDT',
            ['key' => 'value']
        );

        $this->assertNotNull($wallet);
        $this->assertEquals('BDT', $wallet->currency);
        $this->assertEquals('0.0000', $wallet->current_balance);
        $this->assertTrue($wallet->active);
        $this->assertEquals('value', $wallet->metadata['key']);

        // 2. Reject duplicate wallet for same owner/currency
        try {
            $this->walletService->createWallet(
                $this->tenantA->id,
                Customer::class,
                $this->customerA->id,
                'BDT'
            );
            $this->fail('Created duplicate wallet.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('wallet', $e->errors());
        }

        // 3. Reject invalid non-associative metadata
        try {
            $this->walletService->createWallet(
                $this->tenantA->id,
                Customer::class,
                $this->customerA->id,
                'USD',
                ['junk_list_element_1', 'junk_list_element_2']
            );
            $this->fail('Created wallet with non-associative list metadata.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('metadata', $e->errors());
        }
    }

    /**
     * Test high-precision credit and debit mutations with BCMath.
     */
    public function test_wallet_balance_mutation_credit_and_debit(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $wallet = $this->walletService->createWallet(
            $this->tenantA->id,
            Customer::class,
            $this->customerA->id,
            'BDT'
        );

        // 1. Credit mutation
        $creditLedger = $this->walletService->mutateBalance(
            $this->tenantA->id,
            $wallet->id,
            TransactionDirection::CREDIT->value,
            '100.5025',
            TransactionType::MANUAL_CREDIT->value,
            ['notes' => 'Initial deposit', 'metadata' => ['source' => 'cash']]
        );

        $wallet->refresh();
        $this->assertEquals('100.5025', $wallet->current_balance);
        $this->assertEquals('0.0000', $creditLedger->balance_before);
        $this->assertEquals('100.5025', $creditLedger->balance_after);
        $this->assertEquals('100.5025', $creditLedger->amount);
        $this->assertEquals('cash', $creditLedger->metadata['source']);

        // 2. Debit mutation
        $debitLedger = $this->walletService->mutateBalance(
            $this->tenantA->id,
            $wallet->id,
            TransactionDirection::DEBIT->value,
            '40.2510',
            TransactionType::MANUAL_DEBIT->value,
            ['notes' => 'Service payment']
        );

        $wallet->refresh();
        $this->assertEquals('60.2515', $wallet->current_balance);
        $this->assertEquals('100.5025', $debitLedger->balance_before);
        $this->assertEquals('60.2515', $debitLedger->balance_after);
        $this->assertEquals('40.2510', $debitLedger->amount);
    }

    /**
     * Test balance sufficiency checks.
     */
    public function test_wallet_insufficient_balance_rejection(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $wallet = $this->walletService->createWallet(
            $this->tenantA->id,
            Customer::class,
            $this->customerA->id,
            'BDT'
        );

        // Try debiting 1.0000 BDT from 0.0000 balance
        try {
            $this->walletService->mutateBalance(
                $this->tenantA->id,
                $wallet->id,
                TransactionDirection::DEBIT->value,
                '1.0000',
                TransactionType::MANUAL_DEBIT->value
            );
            $this->fail('Debited wallet beyond its balance.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('balance', $e->errors());
        }

        $wallet->refresh();
        $this->assertEquals('0.0000', $wallet->current_balance);
    }

    /**
     * Test idempotency guard rules.
     */
    public function test_wallet_idempotency_guard(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $wallet = $this->walletService->createWallet(
            $this->tenantA->id,
            Customer::class,
            $this->customerA->id,
            'BDT'
        );

        // Initiate credit transaction with idempotency key
        $opt = ['idempotency_key' => 'tx-unique-1234'];
        
        $entry1 = $this->walletService->mutateBalance(
            $this->tenantA->id,
            $wallet->id,
            TransactionDirection::CREDIT->value,
            '50.0000',
            TransactionType::MANUAL_CREDIT->value,
            $opt
        );

        // Re-submit identical transaction
        $entry2 = $this->walletService->mutateBalance(
            $this->tenantA->id,
            $wallet->id,
            TransactionDirection::CREDIT->value,
            '50.0000',
            TransactionType::MANUAL_CREDIT->value,
            $opt
        );

        // Assert exactly same entry object returned (no duplication, no double balance modification)
        $this->assertEquals($entry1->id, $entry2->id);
        $this->assertEquals('50.0000', $wallet->fresh()->current_balance);
        $this->assertCount(1, LedgerEntry::all());
    }

    /**
     * Test wallet inactive state blocks transactions.
     */
    public function test_wallet_inactive_state_blocks_mutation(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $wallet = $this->walletService->createWallet(
            $this->tenantA->id,
            Customer::class,
            $this->customerA->id,
            'BDT'
        );

        // Deactivate wallet
        $wallet->active = false;
        $wallet->save();

        try {
            $this->walletService->mutateBalance(
                $this->tenantA->id,
                $wallet->id,
                TransactionDirection::CREDIT->value,
                '10.0000',
                TransactionType::MANUAL_CREDIT->value
            );
            $this->fail('Mutated inactive wallet.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('wallet', $e->errors());
        }
    }

    /**
     * Test restrictive delete database integrity.
     */
    public function test_wallet_restrictive_delete_integrity(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $wallet = $this->walletService->createWallet(
            $this->tenantA->id,
            Customer::class,
            $this->customerA->id,
            'BDT'
        );

        // Mutate balance to create a ledger entry
        $this->walletService->mutateBalance(
            $this->tenantA->id,
            $wallet->id,
            TransactionDirection::CREDIT->value,
            '10.0000',
            TransactionType::MANUAL_CREDIT->value
        );

        // Attempting to delete the parent wallet must raise database constraint violation
        $this->expectException(QueryException::class);
        $wallet->delete();
    }

    /**
     * Test tenant boundary leak protection.
     */
    public function test_wallet_tenant_isolation_leak_protection(): void
    {
        $tenantManager = app(TenantManager::class);

        // 1. Create wallet under Tenant A
        $tenantManager->setTenant($this->tenantA);
        $walletA = $this->walletService->createWallet(
            $this->tenantA->id,
            Customer::class,
            $this->customerA->id,
            'BDT'
        );

        // 2. Switching to Tenant B, executing queries or balance mutation on Tenant A wallet must fail
        $tenantManager->setTenant($this->tenantB);
        $this->assertCount(0, Wallet::all()); // Query isolation

        try {
            $this->walletService->mutateBalance(
                $this->tenantB->id,
                $walletA->id,
                TransactionDirection::CREDIT->value,
                '10.0000',
                TransactionType::MANUAL_CREDIT->value
            );
            $this->fail('Mutated cross-tenant wallet.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertTrue(true); // isolated lookup failed (expected)
        }
    }

    /**
     * Test RBAC Policy controls.
     */
    public function test_wallet_policies_enforce_rbac_correctly(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $wallet = $this->walletService->createWallet(
            $this->tenantA->id,
            Customer::class,
            $this->customerA->id,
            'BDT'
        );

        // Create Permissions
        $viewPermission = Permission::create([
            'module' => 'billing',
            'action' => 'view_wallet',
            'slug' => 'billing.wallet.view',
        ]);

        $managePermission = Permission::create([
            'module' => 'billing',
            'action' => 'manage_wallet',
            'slug' => 'billing.wallet.manage',
        ]);

        // Scenario 1: Staff User with view-only permission
        $viewRole = Role::create(['name' => 'Wallet Viewer', 'slug' => 'wallet-viewer']);
        $viewRole->permissions()->attach($viewPermission);

        $viewerStaff = StaffUser::create([
            'name' => 'Wallet Viewer Staff',
            'email' => 'viewer@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
            'tenant_id' => $this->tenantA->id,
        ]);
        $viewerStaff->roles()->attach($viewRole);

        // Act as viewer
        $this->actingAs($viewerStaff, 'sanctum');

        $this->assertTrue(Gate::allows('view', $wallet));
        $this->assertFalse(Gate::allows('create', Wallet::class));
        $this->assertFalse(Gate::allows('update', $wallet));
        $this->assertFalse(Gate::allows('delete', $wallet));

        // Scenario 2: Staff User with manage permission
        $managerRole = Role::create(['name' => 'Wallet Manager', 'slug' => 'wallet-manager']);
        $managerRole->permissions()->attach($managePermission);

        $managerStaff = StaffUser::create([
            'name' => 'Wallet Manager Staff',
            'email' => 'manager@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
            'tenant_id' => $this->tenantA->id,
        ]);
        $managerStaff->roles()->attach($managerRole);

        // Act as manager
        $this->actingAs($managerStaff, 'sanctum');

        $this->assertTrue(Gate::allows('create', Wallet::class));
        $this->assertTrue(Gate::allows('update', $wallet));
        $this->assertTrue(Gate::allows('delete', $wallet));

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

        $this->assertFalse(Gate::allows('view', $wallet));
        $this->assertFalse(Gate::allows('update', $wallet));
        $this->assertFalse(Gate::allows('delete', $wallet));
    }
}
