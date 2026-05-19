<?php

declare(strict_types=1);

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\TenantDomain;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

DB::transaction(function () {
    // 1. Clean existing records
    DB::table('ledger_entries')->delete();
    DB::table('wallets')->delete();
    DB::table('tax_rules')->delete();
    DB::table('invoice_line_items')->delete();
    DB::table('invoices')->delete();
    DB::table('customer_service_subscriptions')->delete();
    DB::table('service_packages')->delete();
    DB::table('customer_contracts')->delete();
    DB::table('customer_documents')->delete();
    DB::table('customer_contacts')->delete();
    DB::table('customer_addresses')->delete();
    DB::table('customers')->delete();
    DB::table('staff_user_roles')->delete();
    DB::table('role_permissions')->delete();
    DB::table('staff_users')->delete();
    DB::table('roles')->delete();
    DB::table('permissions')->delete();
    DB::table('tenant_domains')->delete();
    DB::table('tenants')->delete();
    DB::table('plans')->delete();

    echo "Cleared old data.\n";

    // 2. Create Plan
    $plan = Plan::create([
        'name' => 'Premium Plan',
        'slug' => 'premium',
        'price_monthly' => 50.00,
        'price_yearly' => 500.00,
    ]);

    // 3. Create Tenant
    $tenant = Tenant::create([
        'uuid' => '00000000-0000-0000-0000-000000000001',
        'slug' => 'demo-tenant',
        'name' => 'Demo ISP LLC',
        'status' => 'active',
        'plan_id' => $plan->id,
    ]);

    // 4. Create Tenant Domain mapping to localhost
    TenantDomain::create([
        'tenant_id' => $tenant->id,
        'domain' => 'localhost',
        'is_primary' => true,
        'ssl_status' => 'active',
    ]);

    TenantDomain::create([
        'tenant_id' => $tenant->id,
        'domain' => '127.0.0.1',
        'is_primary' => false,
        'ssl_status' => 'active',
    ]);

    echo "Registered domains localhost and 127.0.0.1 for Demo Tenant.\n";

    // 5. Create Permissions
    $permissionsList = [
        ['module' => 'billing', 'action' => 'view_wallet', 'slug' => 'billing.wallet.view'],
        ['module' => 'billing', 'action' => 'manage_wallet', 'slug' => 'billing.wallet.manage'],
        ['module' => 'billing', 'action' => 'view_tax', 'slug' => 'billing.tax.view'],
        ['module' => 'billing', 'action' => 'manage_tax', 'slug' => 'billing.tax.manage'],
        ['module' => 'billing', 'action' => 'view_invoice', 'slug' => 'billing.invoice.view'],
        ['module' => 'billing', 'action' => 'manage_invoice', 'slug' => 'billing.invoice.manage'],
    ];

    $createdPermissions = [];
    foreach ($permissionsList as $p) {
        $createdPermissions[] = Permission::create($p);
    }

    // 6. Create Super Admin Role
    $role = Role::create([
        'name' => 'Administrator',
        'slug' => 'administrator',
    ]);

    // Attach all permissions to Admin role
    foreach ($createdPermissions as $perm) {
        $role->permissions()->attach($perm);
    }

    // 7. Create Staff User
    $staff = StaffUser::create([
        'tenant_id' => $tenant->id,
        'name' => 'Admin User',
        'email' => 'admin@demo.com',
        'password' => Hash::make('admin1234'),
        'status' => 'active',
    ]);

    $staff->roles()->attach($role);

    echo "Created Staff Admin User successfully:\n";
    echo "Email: admin@demo.com\n";
    echo "Password: admin1234\n";
});
