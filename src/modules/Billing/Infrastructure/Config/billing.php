<?php

declare(strict_types=1);

return [
    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY', ''),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],
    'default_currency' => env('BILLING_DEFAULT_CURRENCY', 'usd'),
    'default_tax_rate' => (float) env('BILLING_DEFAULT_TAX_RATE', 0.10),
];
