<?php

use App\Modules\CRM\Http\Livewire\LeadIndex;
use App\Modules\CRM\Http\Livewire\LeadDetail;
use Illuminate\Support\Facades\Route;

Route::get('/leads', LeadIndex::class)->name('crm.leads.index');
Route::get('/leads/{id}', LeadDetail::class)->name('crm.leads.detail');
