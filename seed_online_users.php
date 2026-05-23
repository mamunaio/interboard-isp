<?php

use App\Modules\Network\Models\RadAcct;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Tenant\Models\Tenant;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

$faker = Faker::create();
$tenant = Tenant::first();

// Only select active subscriptions with customers
$subscriptions = CustomerServiceSubscription::with('customer')
    ->where('status', 'active')
    ->get();

if ($subscriptions->isEmpty()) {
    echo "No active subscriptions found.\n";
    exit;
}

// Ensure there are no active sessions first (optional, but good for clean slate)
RadAcct::whereNull('acctstoptime')->update(['acctstoptime' => now()]);

$nasIps = ['10.0.0.1', '10.0.0.2', '10.0.0.3'];

// Create 20 online users
$onlineCount = min(20, $subscriptions->count());
$selectedSubscriptions = $subscriptions->random($onlineCount);

foreach ($selectedSubscriptions as $subscription) {
    $customer = $subscription->customer;
    if (!$customer) continue;

    $startTime = now()->subMinutes(rand(10, 14400)); // 10 mins to 10 days ago
    
    RadAcct::create([
        'tenant_id' => $tenant ? $tenant->id : null,
        'subscription_id' => $subscription->id,
        'acctsessionid' => Str::random(10),
        'acctuniqueid' => Str::uuid()->toString(),
        'username' => $customer->customer_code,
        'nasipaddress' => $faker->randomElement($nasIps),
        'nasporttype' => 'Ethernet',
        'acctstarttime' => $startTime,
        'acctupdatetime' => now()->subMinutes(rand(1, 5)),
        'acctstoptime' => null, // This makes it an ACTIVE session
        'acctinterval' => 300,
        'acctsessiontime' => now()->diffInSeconds($startTime),
        'acctauthentic' => 'RADIUS',
        'acctinputoctets' => rand(10000000, 5000000000), // 10MB to 5GB Upload
        'acctoutputoctets' => rand(50000000, 20000000000), // 50MB to 20GB Download
        'callingstationid' => $faker->macAddress, // MAC Address
        'framedipaddress' => $faker->ipv4, // Assigned IP
        'servicetype' => 'Framed-User',
        'framedprotocol' => 'PPP',
    ]);
}

echo "{$onlineCount} Demo online users generated successfully.\n";
