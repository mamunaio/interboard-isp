<?php

use App\Modules\Notification\Models\NotificationLog;
use App\Modules\Customer\Models\Customer;
use App\Modules\Tenant\Models\Tenant;
use Faker\Factory as Faker;

$faker = Faker::create();
$tenant = Tenant::first();

$channels = ['email', 'sms'];
$statuses = ['sent', 'sent', 'sent', 'failed', 'pending']; // Biased towards 'sent'

$customers = Customer::all();

if ($customers->isEmpty()) {
    echo "No customers found. Run customer seeder first.\n";
    exit;
}

for($i = 0; $i < 30; $i++) {
    $customer = $customers->random();
    $channel = $faker->randomElement($channels);
    $status = $faker->randomElement($statuses);
    
    $recipient = $channel === 'email' ? $customer->email : $customer->phone;
    
    // If no email/phone for the customer, use a fake one
    if (!$recipient) {
        $recipient = $channel === 'email' ? $faker->safeEmail : '01' . $faker->numerify('#########');
    }
    
    NotificationLog::create([
        'tenant_id' => $tenant ? $tenant->id : null,
        'customer_id' => $customer->id,
        'channel' => $channel,
        'recipient' => $recipient,
        'message' => $channel === 'email' ? $faker->paragraph : $faker->sentence,
        'status' => $status,
        'error_message' => $status === 'failed' ? 'Connection timeout or invalid recipient.' : null,
        'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
        'updated_at' => $faker->dateTimeBetween('-1 week', 'now'),
    ]);
}

echo "30 Demo notification logs generated successfully.\n";
