<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TENANTS ===\n";
print_r(App\Modules\Tenant\Models\Tenant::all()->toArray());

echo "\n=== TENANT DOMAINS ===\n";
print_r(App\Modules\Tenant\Models\TenantDomain::all()->toArray());
