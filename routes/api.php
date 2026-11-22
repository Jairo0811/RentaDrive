<?php

declare(strict_types=1);

use App\Http\Controllers\DepositController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/sandbox/webhook', [DepositController::class, 'webhook'])
    ->middleware('throttle:30,1')
    ->name('api.payments.sandbox.webhook');

// Observabilidad: /health es público y mínimo; /health/details exige token Bearer (HEALTH_TOKEN).
Route::get('/health', [HealthController::class, 'live'])->middleware('throttle:60,1')->name('api.health');
Route::get('/health/details', [HealthController::class, 'details'])->middleware('throttle:20,1')->name('api.health.details');
