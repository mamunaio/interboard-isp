<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Tenant\Http\Livewire\SaaSDashboard;
use App\Modules\Tenant\Http\Livewire\TenantManager;

Route::get('/', function () {
    return view('welcome');
});

// SaaS Super Admin Routes
Route::middleware(['web', 'superadmin'])->prefix('saas')->group(function () {
    Route::get('/', SaaSDashboard::class)->name('saas.dashboard');
    Route::get('/tenants', TenantManager::class)->name('saas.tenants');
});
