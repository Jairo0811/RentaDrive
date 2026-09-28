<?php

use App\Http\Controllers\Api\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/payments/{gateway}', PaymentWebhookController::class)
    ->middleware('throttle:webhook')
    ->name('api.payments.webhook');
