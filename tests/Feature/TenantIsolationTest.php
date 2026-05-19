<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\TenantDomain;
use App\Modules\Auth\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize test base structures
        $this->plan = Plan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise',
            'price_monthly' => 100.00,
            'price_yearly' => 1000.00,
        ]);

        $this->tenantA = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'slug' => 'tenant-a',
            'name' => 'Tenant A Ltd',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        TenantDomain::create([
            'tenant_id' => $this->tenantA->id,
            'domain' => 'tenant-a.com',
            'is_primary' => true,
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'slug' => 'tenant-b',
            'name' => 'Tenant B Corp',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        TenantDomain::create([
            'tenant_id' => $this->tenantB->id,
            'domain' => 'tenant-b.com',
            'is_primary' => true,
        ]);
    }

    /**
     * Test that model creation automatically sets the active tenant ID.
     */
    public function test_model_creation_automatically_sets_tenant_id(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $user = StaffUser::create([
            'name' => 'John A',
            'email' => 'john@tenant-a.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $this->assertEquals($this->tenantA->id, $user->tenant_id);
    }

    /**
     * Test that TenantScope separates customer/staff data dynamically.
     */
    public function test_tenant_scope_enforces_isolation_boundaries(): void
    {
        $tenantManager = app(TenantManager::class);

        // 1. Create Staff for Tenant A
        $tenantManager->setTenant($this->tenantA);
        StaffUser::create([
            'name' => 'Staff A',
            'email' => 'staff@tenant-a.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        // 2. Create Staff for Tenant B
        $tenantManager->setTenant($this->tenantB);
        StaffUser::create([
            'name' => 'Staff B',
            'email' => 'staff@tenant-b.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        // 3. Query under Tenant A context
        $tenantManager->setTenant($this->tenantA);
        $this->assertCount(1, StaffUser::all());
        $this->assertEquals('Staff A', StaffUser::first()->name);

        // 4. Query under Tenant B context
        $tenantManager->setTenant($this->tenantB);
        $this->assertCount(1, StaffUser::all());
        $this->assertEquals('Staff B', StaffUser::first()->name);
    }

    /**
     * Test that global queries can temporarily bypass the TenantScope.
     */
    public function test_without_tenant_scope_allows_global_queries(): void
    {
        $tenantManager = app(TenantManager::class);

        // Create entries across tenants
        $tenantManager->setTenant($this->tenantA);
        StaffUser::create(['name' => 'A', 'email' => 'a@t.com', 'password' => 'p', 'status' => 'active']);

        $tenantManager->setTenant($this->tenantB);
        StaffUser::create(['name' => 'B', 'email' => 'b@t.com', 'password' => 'p', 'status' => 'active']);

        // Check global count inside bypass
        $totalCount = $tenantManager->withoutTenantScope(function () {
            return StaffUser::count();
        });

        $this->assertEquals(2, $totalCount);
    }
}
