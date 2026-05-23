<?php

use App\Modules\Billing\Models\GatewayPaymentAttempt;
use App\Modules\Customer\Models\Customer;
use App\Modules\Tenant\Models\Tenant;
use Faker\Factory as Faker;

$faker = Faker::create();
$tenant = Tenant::first();

$customers = Customer::all();

if ($customers->isEmpty()) {
    echo "No customers found. Run customer seeder first.\n";
    exit;
}

$gateways = ['BKASH', 'NAGAD', 'SSLCOMMERZ', 'SSLCOMMERZ'];
$statuses = ['Verified', 'Pending', 'Failed', 'Verified'];

$latest = GatewayPaymentAttempt::orderBy('id', 'desc')->first();
$startId = $latest ? $latest->id + 1 : 1;

for ($i = 0; $i < 30; $i++) {
    $customer = $customers->random();
    $gateway = $faker->randomElement($gateways);
    $status = $faker->randomElement($statuses);
    
    $attemptRef = 'ATT-' . $gateway . '-' . ($startId + $i);
    $txnId = $status === 'Pending' ? 'Pending-' . uniqid() : 'TXN-' . strtoupper($faker->bothify('?##??###'));
    
    $verifiedAt = $status === 'Verified' ? $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s') : null;

    GatewayPaymentAttempt::create([
        'tenant_id' => $tenant ? $tenant->id : null,
        'customer_id' => $customer->id,
        'gateway' => $gateway,
        'attempt_reference' => $attemptRef,
        'gateway_transaction_id' => $txnId,
        'requested_amount' => $faker->numberBetween(500, 3000),
        'currency' => 'BDT',
        'status' => $status,
        'gateway_payload' => [
            'request_ip' => $faker->ipv4,
            'user_agent' => $faker->userAgent,
            'api_response_code' => $status === 'Verified' ? 200 : ($status === 'Failed' ? 400 : 202)
        ],
        'verified_at' => $verifiedAt,
        'payment_receipt_id' => null, // Optional for dummy data
    ]);
}

echo "30 Demo gateway attempts generated successfully.\n";
