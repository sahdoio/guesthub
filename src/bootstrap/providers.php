<?php

return [
    Modules\Shared\Infrastructure\Providers\PsrHttpServiceProvider::class,
    Modules\Shared\Infrastructure\Providers\EventStoreServiceProvider::class,
    Modules\IAM\Infrastructure\Providers\IAMServiceProvider::class,
    Modules\Stay\Infrastructure\Providers\StayServiceProvider::class,
    Modules\Billing\Infrastructure\Providers\BillingServiceProvider::class,
];
