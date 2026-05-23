<?php

use App\Modules\Provisioning\Models\ServiceProvisioningEvent;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Tenant\Models\Tenant;
use Faker\Factory as Faker;

$faker = Faker::create();
$tenant = Tenant::first();

// Only select subscriptions that have a customer assigned
$subscriptions = CustomerServiceSubscription::with('customer')->get();

if ($subscriptions->isEmpty()) {
    echo "No subscriptions found. Run subscription seeder first.\n";
    exit;
}

$actionTypes = ['provision', 'suspend', 'reactivate', 'profile_change', 'provision', 'provision'];
$statuses = ['completed', 'completed', 'completed', 'failed', 'pending', 'processing'];
$providers = ['mikrotik', 'huawei_olt', 'zte_olt'];

for ($i = 0; $i < 30; $i++) {
    $subscription = $subscriptions->random();
    $customer = $subscription->customer;
    
    if (!$customer) continue;

    $action = $faker->randomElement($actionTypes);
    $status = $faker->randomElement($statuses);
    
    $executedAt = in_array($status, ['completed', 'failed']) 
        ? $faker->dateTimeBetween('-1 week', 'now')->format('Y-m-d H:i:s') 
        : null;
        
    $errorMessage = $status === 'failed' ? 'Connection timed out to NAS.' : null;

    ServiceProvisioningEvent::create([
        'tenant_id' => $tenant ? $tenant->id : null,
        'customer_id' => $customer->id,
        'subscription_id' => $subscription->id,
        'action_type' => $action,
        'status' => $status,
        'provider' => $faker->randomElement($providers),
        'request_payload_json' => [
            'username' => $customer->customer_code,
            'profile' => '50Mbps_Plan',
            'ip_address' => $faker->ipv4,
        ],
        'response_payload_json' => $status === 'completed' ? ['success' => true, 'message' => 'Provisioned successfully'] : null,
        'error_message' => $errorMessage,
        'retry_count' => $status === 'failed' ? $faker->numberBetween(1, 3) : 0,
        'executed_at' => $executedAt,
    ]);
}

echo "30 Demo provisioning events generated successfully.\n";
