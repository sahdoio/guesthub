<?php

declare(strict_types=1);

use Illuminate\Support\Env;

return [
    'gateway' => Env::get('BILLING_GATEWAY', 'simulated'),
    'stripe' => [
        'secret_key' => Env::get('STRIPE_SECRET_KEY', ''),
        'publishable_key' => Env::get('STRIPE_PUBLISHABLE_KEY', ''),
        'webhook_secret' => Env::get('STRIPE_WEBHOOK_SECRET', ''),
    ],
    'default_currency' => Env::get('BILLING_DEFAULT_CURRENCY', 'usd'),
    'default_tax_rate' => (float) Env::get('BILLING_DEFAULT_TAX_RATE', 0.10),
];
