<?php

declare(strict_types=1);

use App\Http\Controllers\DepositController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/sandbox/webhook', [DepositController::class, 'webhook'])
    ->middleware('throttle:30,1')
    ->name('api.payments.sandbox.webhook');
