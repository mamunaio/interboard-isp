<?php

use App\Modules\Auth\Http\Controllers\Api\AuthApiController;
use App\Modules\Auth\Http\Controllers\Api\StaffUserController;
use Illuminate\Support\Facades\Route;

// Public Auth Endpoints
Route::post('/auth/login', [AuthApiController::class, 'login']);
Route::post('/auth/superadmin/login', [AuthApiController::class, 'superadminLogin']);

// Authenticated Endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);
    Route::get('/staff', [StaffUserController::class, 'index']);
    Route::post('/staff', [StaffUserController::class, 'store']);
});
