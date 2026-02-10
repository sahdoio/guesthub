<?php

return [
    App\Providers\AppServiceProvider::class,
    Modules\Reservation\Infrastructure\Providers\ReservationServiceProvider::class,
    Modules\Guest\Infrastructure\Providers\GuestServiceProvider::class,
];
