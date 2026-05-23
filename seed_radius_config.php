<?php

use App\Modules\Network\Models\RadCheck;
use App\Modules\Network\Models\RadReply;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Tenant\Models\Tenant;
use Faker\Factory as Faker;

$faker = Faker::create();
$tenant = Tenant::first();

$subscriptions = CustomerServiceSubscription::with(['customer', 'servicePlan'])->get();

if ($subscriptions->isEmpty()) {
    echo "No subscriptions found. Run subscription seeder first.\n";
    exit;
}

$count = 0;

foreach ($subscriptions as $subscription) {
    $customer = $subscription->customer;
    if (!$customer) continue;

    $username = $customer->customer_code;
    $password = $faker->password;
    $ip = $faker->ipv4;
    $speed = '50M/50M'; // Default speed

    if ($subscription->servicePlan) {
        // Just extract a number from plan name to make speed look realistic
        preg_match('/\d+/', $subscription->servicePlan->name, $matches);
        if (!empty($matches)) {
            $speed = $matches[0] . 'M/' . $matches[0] . 'M';
        }
    }

    // Insert RadCheck
    RadCheck::firstOrCreate([
        'username' => $username,
        'attribute' => 'Cleartext-Password',
    ], [
        'tenant_id' => $tenant ? $tenant->id : null,
        'subscription_id' => $subscription->id,
        'op' => ':=',
        'value' => $password,
    ]);

    // Insert RadReply for Mikrotik-Rate-Limit
    RadReply::firstOrCreate([
        'username' => $username,
        'attribute' => 'Mikrotik-Rate-Limit',
    ], [
        'tenant_id' => $tenant ? $tenant->id : null,
        'subscription_id' => $subscription->id,
        'op' => '=',
        'value' => $speed,
    ]);

    // Insert RadReply for Framed-IP-Address
    RadReply::firstOrCreate([
        'username' => $username,
        'attribute' => 'Framed-IP-Address',
    ], [
        'tenant_id' => $tenant ? $tenant->id : null,
        'subscription_id' => $subscription->id,
        'op' => '=',
        'value' => $ip,
    ]);
    
    // Insert RadReply for Framed-Pool
    RadReply::firstOrCreate([
        'username' => $username,
        'attribute' => 'Framed-Pool',
    ], [
        'tenant_id' => $tenant ? $tenant->id : null,
        'subscription_id' => $subscription->id,
        'op' => '=',
        'value' => 'POOL_NAT',
    ]);

    $count++;
}

echo "{$count} Customers configured in RADIUS Check and Reply tables.\n";
