<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenant;
    protected StaffUser $staffUser;
    protected Role $adminRole;
    protected Permission $viewStaffPermission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Scale',
            'slug' => 'scale',
            'price_monthly' => 150.00,
            'price_yearly' => 1500.00,
        ]);

        $this->tenant = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'slug' => 'beta',
            'name' => 'Beta ISP',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenant);

        // 1. Create RBAC entities
        $this->viewStaffPermission = Permission::create([
            'module' => 'customer',
            'action' => 'view',
            'slug' => 'customer.staff.view',
            'description' => 'View staff user list',
        ]);

        $this->adminRole = Role::create([
            'name' => 'Tenant Administrator',
            'slug' => 'tenant-admin',
            'description' => 'Admin role for tenant staff',
        ]);

        // Assign permission to role
        $this->adminRole->permissions()->attach($this->viewStaffPermission);

        // 2. Create Staff user and attach role
        $this->staffUser = StaffUser::create([
            'name' => 'Rbac Tester',
            'email' => 'rbac@beta.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $this->staffUser->roles()->attach($this->adminRole);
    }

    /**
     * Test that authorized staff user has access.
     */
    public function test_authorized_staff_user_can_view_staff_list(): void
    {
        // Act like the staff user
        $this->actingAs($this->staffUser, 'sanctum');

        // Assert Gate check passes
        $this->assertTrue(Gate::allows('viewAny', StaffUser::class));
    }

    /**
     * Test that unauthorized staff user is blocked.
     */
    public function test_unauthorized_staff_user_cannot_create_staff(): void
    {
        $this->actingAs($this->staffUser, 'sanctum');

        // Assert they don't have permission to create staff
        $this->assertFalse(Gate::allows('create', StaffUser::class));
    }

    /**
     * Test that Tenant Owner role acts as a superuser bypass.
     */
    public function test_tenant_owner_role_instantly_bypasses_all_gates(): void
    {
        $ownerRole = Role::create([
            'name' => 'Tenant Owner',
            'slug' => 'tenant-owner',
            'description' => 'Global owner of the tenant account',
        ]);

        $ownerUser = StaffUser::create([
            'name' => 'Owner Person',
            'email' => 'owner@beta.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $ownerUser->roles()->attach($ownerRole);

        $this->actingAs($ownerUser, 'sanctum');

        // Tenant owner should bypass all checks and allow 'create' even without explicit attachment
        $this->assertTrue(Gate::allows('create', StaffUser::class));
        $this->assertTrue(Gate::allows('viewAny', StaffUser::class));
    }
}
