<?php

declare(strict_types=1);

return [
    App\Providers\HorizonServiceProvider::class,
    Modules\Billing\Infrastructure\Providers\BillingServiceProvider::class,
    Modules\IAM\Infrastructure\Providers\IAMServiceProvider::class,
    Modules\Shared\Infrastructure\Providers\EventStoreServiceProvider::class,
    Modules\Shared\Infrastructure\Providers\PsrHttpServiceProvider::class,
    Modules\Stay\Infrastructure\Providers\StayServiceProvider::class,
];
