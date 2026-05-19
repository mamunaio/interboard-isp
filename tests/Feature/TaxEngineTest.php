<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Billing\Models\TaxRule;
use App\Modules\Billing\Enums\TaxType;
use App\Modules\Billing\Enums\TaxCalculationMode;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Billing\Services\TaxRuleService;
use App\Modules\Billing\Services\TaxCalculationService;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaxEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected TaxRuleService $taxRuleService;
    protected TaxCalculationService $taxCalculationService;

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
            'uuid' => '00000000-0000-0000-0000-000000000301',
            'slug' => 'tenant-301',
            'name' => 'Tenant 301',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000302',
            'slug' => 'tenant-302',
            'name' => 'Tenant 302',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->taxRuleService = app(TaxRuleService::class);
        $this->taxCalculationService = app(TaxCalculationService::class);
    }

    /**
     * Test successful creation, validation, and unique constraints of tax rules.
     */
    public function test_tax_rule_crud_and_validations(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // 1. Create a percentage exclusive tax rule successfully
        $rule1 = $this->taxRuleService->createTaxRule($this->tenantA->id, [
            'name' => 'VAT 15%',
            'code' => 'VAT',
            'tax_type' => TaxType::PERCENTAGE->value,
            'calculation_mode' => TaxCalculationMode::EXCLUSIVE->value,
            'rate' => '15.0000',
            'applies_to_invoice_types' => [InvoiceType::SUBSCRIPTION->value, InvoiceType::HARDWARE->value],
        ]);

        $this->assertNotNull($rule1);
        $this->assertEquals('VAT', $rule1->code);
        $this->assertEquals('15.0000', $rule1->rate);
        $this->assertEquals('0.0000', $rule1->fixed_amount);
        $this->assertFalse($rule1->is_compound);
        $this->assertNull($rule1->compound_order);

        // 2. Reject duplicate code for same tenant
        try {
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'VAT Duplicate',
                'code' => 'VAT',
                'tax_type' => TaxType::PERCENTAGE->value,
                'calculation_mode' => TaxCalculationMode::EXCLUSIVE->value,
                'rate' => '5.0000',
            ]);
            $this->fail('Created duplicate tax rule code.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }

        // 3. Reject invalid percentage rate boundaries
        try {
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'Bad percentage',
                'code' => 'BAD_PCT',
                'tax_type' => TaxType::PERCENTAGE->value,
                'rate' => '105.0000',
            ]);
            $this->fail('Created percentage tax rule with rate > 100.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rate', $e->errors());
        }

        // 4. Reject invalid fixed amount boundaries
        try {
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'Bad fixed',
                'code' => 'BAD_FIX',
                'tax_type' => TaxType::FIXED->value,
                'fixed_amount' => '-1.5000',
            ]);
            $this->fail('Created fixed tax rule with negative amount.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fixed_amount', $e->errors());
        }

        // 5. Reject invalid compound constraints (compound true requires order >= 1)
        try {
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'Bad compound',
                'code' => 'BAD_CMP',
                'tax_type' => TaxType::PERCENTAGE->value,
                'rate' => '10.0000',
                'is_compound' => true,
                'compound_order' => null,
            ]);
            $this->fail('Created compound tax rule without order.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('compound_order', $e->errors());
        }

        // 6. Reject invalid compound order (compound false requires order null)
        try {
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'Bad compound',
                'code' => 'BAD_CMP2',
                'tax_type' => TaxType::PERCENTAGE->value,
                'rate' => '10.0000',
                'is_compound' => false,
                'compound_order' => 2,
            ]);
            $this->fail('Created non-compound tax rule with non-null order.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('compound_order', $e->errors());
        }

        // 7. Reject invalid invoice type array contents
        try {
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'Bad type array',
                'code' => 'BAD_ARRAY',
                'tax_type' => TaxType::PERCENTAGE->value,
                'rate' => '10.0000',
                'applies_to_invoice_types' => ['arbitrary_junk_string'],
            ]);
            $this->fail('Created tax rule with invalid invoice type element.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('applies_to_invoice_types', $e->errors());
        }
    }

    /**
     * Test pure tax calculator with percentage exclusive and fixed exclusive rules.
     */
    public function test_exclusive_percentage_and_fixed_calculations(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $rules = [
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'VAT 10%',
                'code' => 'VAT10',
                'tax_type' => TaxType::PERCENTAGE->value,
                'calculation_mode' => TaxCalculationMode::EXCLUSIVE->value,
                'rate' => '10.0000',
                'applies_to_invoice_types' => [InvoiceType::SUBSCRIPTION->value],
            ]),
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'Surcharge 25',
                'code' => 'SUR25',
                'tax_type' => TaxType::FIXED->value,
                'calculation_mode' => TaxCalculationMode::EXCLUSIVE->value,
                'fixed_amount' => '25.0000',
                'applies_to_invoice_types' => [InvoiceType::SUBSCRIPTION->value],
            ])
        ];

        // Calculate: Subtotal = 1000
        $result = $this->taxCalculationService->calculateTaxes('1000.0000', InvoiceType::SUBSCRIPTION->value, $rules);

        // Expected base = 1000.0000
        $this->assertEquals('1000.0000', $result['base_decomposition']);
        // Expected VAT = 1000 * 10% = 100.0000
        // Expected SUR = 25.0000
        // Total Tax = 125.0000
        $this->assertEquals('125.0000', $result['tax_total']);
        // Grand Total = 1125.0000
        $this->assertEquals('1125.0000', $result['grand_total']);
        $this->assertCount(2, $result['tax_details']);
    }

    /**
     * Test inclusive base decomposition and fixed inclusive rules.
     */
    public function test_inclusive_percentage_and_fixed_base_decomposition(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $rules = [
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'Inclusive VAT 10%',
                'code' => 'INC_VAT',
                'tax_type' => TaxType::PERCENTAGE->value,
                'calculation_mode' => TaxCalculationMode::INCLUSIVE->value,
                'rate' => '10.0000',
                'applies_to_invoice_types' => [InvoiceType::SUBSCRIPTION->value],
            ]),
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'Inclusive Surcharge 50',
                'code' => 'INC_SUR',
                'tax_type' => TaxType::FIXED->value,
                'calculation_mode' => TaxCalculationMode::INCLUSIVE->value,
                'fixed_amount' => '50.0000',
                'applies_to_invoice_types' => [InvoiceType::SUBSCRIPTION->value],
            ])
        ];

        // Calculate on subtotal (total) = 1150.0000
        $result = $this->taxCalculationService->calculateTaxes('1150.0000', InvoiceType::SUBSCRIPTION->value, $rules);

        // Expected steps:
        // 1. Subtract fixed inclusive: 1150 - 50 = 1100
        // 2. Extract base from remaining: 1100 / (1 + 10%) = 1000.0000
        $this->assertEquals('1000.0000', $result['base_decomposition']);
        // 3. Expected VAT amount = 1000 * 10% = 100.0000
        // 4. Expected fixed amount = 50.0000
        // 5. Total tax = 150.0000
        $this->assertEquals('150.0000', $result['tax_total']);
        // 6. Grand total must equal the original input (1150)
        $this->assertEquals('1150.0000', $result['grand_total']);
    }

    /**
     * Test compound exclusive layering sequence.
     */
    public function test_compound_exclusive_layering_sequencing(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $rules = [
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'VAT 10% Exclusive',
                'code' => 'VAT10_EXC',
                'tax_type' => TaxType::PERCENTAGE->value,
                'calculation_mode' => TaxCalculationMode::EXCLUSIVE->value,
                'rate' => '10.0000',
                'applies_to_invoice_types' => [InvoiceType::SUBSCRIPTION->value],
            ]),
            $this->taxRuleService->createTaxRule($this->tenantA->id, [
                'name' => 'Service Surcharge 5% Compound',
                'code' => 'SRV_CMP',
                'tax_type' => TaxType::PERCENTAGE->value,
                'calculation_mode' => TaxCalculationMode::EXCLUSIVE->value,
                'is_compound' => true,
                'compound_order' => 1,
                'rate' => '5.0000',
                'applies_to_invoice_types' => [InvoiceType::SUBSCRIPTION->value],
            ]),
        ];

        // Base = 1000
        $result = $this->taxCalculationService->calculateTaxes('1000.0000', InvoiceType::SUBSCRIPTION->value, $rules);

        // Expected steps:
        // 1. Exclusive non-compound base = 1000
        // 2. VAT amount = 1000 * 10% = 100.0000. (Running total becomes 1100.0000)
        // 3. Service Surcharge compound (order = 1) calculated on 1100.0000
        //    Amount = 1100.0000 * 5% = 55.0000
        // 4. Total Tax = 100.0000 + 55.0000 = 155.0000
        $this->assertEquals('155.0000', $result['tax_total']);
        // 5. Grand Total = 1155.0000
        $this->assertEquals('1155.0000', $result['grand_total']);
    }

    /**
     * Test tenant isolation guard on tax rules.
     */
    public function test_tenant_isolation_guards_leakage(): void
    {
        $tenantManager = app(TenantManager::class);

        // 1. Create a tax rule for Tenant A
        $tenantManager->setTenant($this->tenantA);
        $this->taxRuleService->createTaxRule($this->tenantA->id, [
            'name' => 'Tenant A Rule',
            'code' => 'T_A',
            'tax_type' => TaxType::PERCENTAGE->value,
            'rate' => '10.0000',
            'applies_to_invoice_types' => [InvoiceType::SUBSCRIPTION->value],
        ]);

        // 2. Create a tax rule for Tenant B
        $tenantManager->setTenant($this->tenantB);
        $this->taxRuleService->createTaxRule($this->tenantB->id, [
            'name' => 'Tenant B Rule',
            'code' => 'T_B',
            'tax_type' => TaxType::PERCENTAGE->value,
            'rate' => '20.0000',
            'applies_to_invoice_types' => [InvoiceType::SUBSCRIPTION->value],
        ]);

        // 3. Verify that under Tenant A context, only Tenant A rules are loaded
        $tenantManager->setTenant($this->tenantA);
        $tenantARules = TaxRule::all();
        $this->assertCount(1, $tenantARules);
        $this->assertEquals('T_A', $tenantARules[0]->code);

        // 4. Verify that calculation does not pull in Tenant B rules
        $result = $this->taxCalculationService->calculateTaxes('1000.0000', InvoiceType::SUBSCRIPTION->value, $tenantARules);
        $this->assertEquals('100.0000', $result['tax_total']);
    }

    /**
     * Test Policy Authorization (RBAC) rules.
     */
    public function test_tax_rule_policies_enforce_rbac_correctly(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $taxRule = $this->taxRuleService->createTaxRule($this->tenantA->id, [
            'name' => 'Policy Test VAT',
            'code' => 'POL_VAT',
            'tax_type' => TaxType::PERCENTAGE->value,
            'rate' => '10.0000',
            'applies_to_invoice_types' => [InvoiceType::SUBSCRIPTION->value],
        ]);

        // Create Permissions
        $viewPermission = Permission::create([
            'module' => 'billing',
            'action' => 'view_tax',
            'slug' => 'billing.tax.view',
        ]);

        $managePermission = Permission::create([
            'module' => 'billing',
            'action' => 'manage_tax',
            'slug' => 'billing.tax.manage',
        ]);

        // Scenario 1: Staff User with view-only permission
        $viewRole = Role::create(['name' => 'Tax Viewer', 'slug' => 'tax-viewer']);
        $viewRole->permissions()->attach($viewPermission);

        $viewerStaff = StaffUser::create([
            'name' => 'Tax Viewer Staff',
            'email' => 'viewer@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
            'tenant_id' => $this->tenantA->id,
        ]);
        $viewerStaff->roles()->attach($viewRole);

        // Act as viewer
        $this->actingAs($viewerStaff, 'sanctum');

        $this->assertTrue(Gate::allows('view', $taxRule));
        $this->assertFalse(Gate::allows('create', TaxRule::class));
        $this->assertFalse(Gate::allows('update', $taxRule));
        $this->assertFalse(Gate::allows('delete', $taxRule));

        // Scenario 2: Staff User with manage permission
        $managerRole = Role::create(['name' => 'Tax Manager', 'slug' => 'tax-manager']);
        $managerRole->permissions()->attach($managePermission);

        $managerStaff = StaffUser::create([
            'name' => 'Tax Manager Staff',
            'email' => 'manager@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
            'tenant_id' => $this->tenantA->id,
        ]);
        $managerStaff->roles()->attach($managerRole);

        // Act as manager
        $this->actingAs($managerStaff, 'sanctum');

        $this->assertTrue(Gate::allows('create', TaxRule::class));
        $this->assertTrue(Gate::allows('update', $taxRule));
        $this->assertTrue(Gate::allows('delete', $taxRule));

        // Scenario 3: Tenant Boundary Protection
        // Act as a Tenant B staff user trying to access Tenant A rule
        $tenantBStaff = StaffUser::create([
            'name' => 'Tenant B Staff',
            'email' => 'staff@tenant-b.com',
            'password' => 'secret123',
            'status' => 'active',
            'tenant_id' => $this->tenantB->id,
        ]);
        $tenantBStaff->roles()->attach($managerRole); // even with manage role

        $this->actingAs($tenantBStaff, 'sanctum');

        $this->assertFalse(Gate::allows('view', $taxRule));
        $this->assertFalse(Gate::allows('update', $taxRule));
        $this->assertFalse(Gate::allows('delete', $taxRule));
    }
}
