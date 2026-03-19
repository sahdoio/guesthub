<?php

return [
    Modules\Shared\Infrastructure\Providers\PsrHttpServiceProvider::class,
    Modules\Shared\Infrastructure\Providers\EventStoreServiceProvider::class,
    Modules\IAM\Infrastructure\Providers\IAMServiceProvider::class,
    Modules\Reservation\Infrastructure\Providers\ReservationServiceProvider::class,
    Modules\User\Infrastructure\Providers\UserServiceProvider::class,
    Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider::class,
];
