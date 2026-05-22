<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Create a superadmin user in the users table
DB::table('users')->insert([
    'name' => 'Super Admin',
    'email' => 'admin@interboard.com',
    'password' => Hash::make('password'),
    'is_superadmin' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Superadmin user created: admin@interboard.com / password\n";
