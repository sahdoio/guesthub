<?php

return [
    App\Providers\AppServiceProvider::class,
    Modules\IAM\Infrastructure\Providers\IAMServiceProvider::class,
    Modules\Reservation\Infrastructure\Providers\ReservationServiceProvider::class,
    Modules\Guest\Infrastructure\Providers\GuestServiceProvider::class,
];
