<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Modules\Auth\Models\StaffUser::first();
    $user->name = 'Ismail Hossain';
    $user->email = 'manager@interboard.io';
    $user->phone = '+880 1234-567890';
    $user->avatar = 'avatars/test.png';
    $user->save();
    echo "Saved successfully.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
