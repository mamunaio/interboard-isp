<?php

use App\Modules\Notification\Http\Livewire\NotificationTemplateManager;
use App\Modules\Notification\Http\Livewire\NotificationLogIndex;
use Illuminate\Support\Facades\Route;

Route::get('/notification-templates', NotificationTemplateManager::class)->name('notification.templates');
Route::get('/notification-logs', NotificationLogIndex::class)->name('notification.logs');
