<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Infrastructure\Stripe\StripeWebhookController;

Route::post('/billing/stripe/webhook', StripeWebhookController::class)
    ->name('billing.stripe.webhook');
