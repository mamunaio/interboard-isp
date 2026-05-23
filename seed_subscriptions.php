<?php

use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\ServicePlan;
use App\Modules\Customer\Models\ServicePackage;
use App\Modules\Tenant\Models\Tenant;
use Faker\Factory as Faker;

$faker = Faker::create();
$tenant = Tenant::first();

$customers = Customer::all();

if ($customers->isEmpty()) {
    echo "No customers found. Run customer seeder first.\n";
    exit;
}

// Fetch some service plans/packages to attach
$plans = ServicePlan::all();
$packages = ServicePackage::all();

if ($plans->isEmpty() && $packages->isEmpty()) {
    // Let's create some dummy plans just in case
    $plan1 = ServicePlan::create(['name' => 'Fiber Starter 50Mbps', 'tenant_id' => $tenant->id ?? null, 'price_monthly' => 500, 'service_type' => 'broadband']);
    $plan2 = ServicePlan::create(['name' => 'Fiber Corporate 100Mbps', 'tenant_id' => $tenant->id ?? null, 'price_monthly' => 1000, 'service_type' => 'broadband']);
    $plans = collect([$plan1, $plan2]);
}

$statuses = ['active', 'active', 'active', 'suspended', 'disconnected'];
$billingModes = ['prepaid', 'postpaid'];
$cycleTypes = ['anniversary', 'calendar'];

for($i = 0; $i < 30; $i++) {
    $customer = $customers->random();
    $status = $faker->randomElement($statuses);
    
    $plan = $plans->isNotEmpty() ? $plans->random() : null;
    $package = $packages->isNotEmpty() ? $packages->random() : null;

    CustomerServiceSubscription::create([
        'tenant_id' => $tenant ? $tenant->id : null,
        'customer_id' => $customer->id,
        'service_plan_id' => $plan ? $plan->id : null,
        'service_package_id' => $package ? $package->id : null,
        'subscription_code' => 'SUB-' . strtoupper($faker->bothify('?##??###')),
        'service_type_snapshot' => 'broadband',
        'status' => $status,
        'billing_mode' => $faker->randomElement($billingModes),
        'billing_cycle_type' => $faker->randomElement($cycleTypes),
        'next_billing_date' => $faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
        'last_billed_at' => $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s'),
        'activated_at' => $faker->dateTimeBetween('-1 year', '-1 month')->format('Y-m-d H:i:s'),
    ]);
}

echo "30 Demo Service Subscriptions generated successfully.\n";
