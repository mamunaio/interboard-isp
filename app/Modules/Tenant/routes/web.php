<?php

use App\Modules\Tenant\Http\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', Dashboard::class)->name('tenant.dashboard');
