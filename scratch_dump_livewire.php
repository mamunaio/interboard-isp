<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Livewire\Livewire;

Livewire::addNamespace('tenant', null, 'App\\Modules\\Tenant\\Http\\Livewire');

try {
    $resolved = app(\Livewire\LivewireManager::class)->new('tenant::dashboard');
    echo "resolved class: " . get_class($resolved) . "\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
