<?php

use App\Modules\Inventory\Http\Livewire\InventoryIndex;
use Illuminate\Support\Facades\Route;

Route::get('/inventory', InventoryIndex::class)->name('inventory.index');
