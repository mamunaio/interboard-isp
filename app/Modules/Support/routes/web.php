<?php

use App\Modules\Support\Http\Livewire\WorkOrderIndex;
use App\Modules\Support\Http\Livewire\TicketIndex;
use App\Modules\Support\Http\Livewire\TicketDetail;
use Illuminate\Support\Facades\Route;

Route::get('/work-orders', WorkOrderIndex::class)->name('support.work-orders');
Route::get('/tickets', TicketIndex::class)->name('support.tickets.index');
Route::get('/tickets/{id}', TicketDetail::class)->name('support.tickets.detail');
