<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Reservation\Application\EventHandler\OnGuestCheckedIn;
use Modules\Reservation\Application\EventHandler\OnGuestCheckedOut;
use Modules\Reservation\Application\EventHandler\OnReservationCancelled;
use Modules\Reservation\Application\EventHandler\OnReservationConfirmed;
use Modules\Reservation\Domain\Event\GuestCheckedIn;
use Modules\Reservation\Domain\Event\GuestCheckedOut;
use Modules\Reservation\Domain\Event\ReservationCancelled;
use Modules\Reservation\Domain\Event\ReservationConfirmed;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Service\InventoryGateway;
use Modules\Reservation\Infrastructure\Integration\InventoryGatewayAdapter;
use Modules\Reservation\Infrastructure\Persistence\QueryBuilderReservationRepository;

final class ReservationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/reservation.php', 'reservation');

        $this->app->bind(ReservationRepository::class, QueryBuilderReservationRepository::class);
        $this->app->bind(InventoryGateway::class, InventoryGatewayAdapter::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');

        Event::listen(ReservationConfirmed::class, OnReservationConfirmed::class);
        Event::listen(ReservationCancelled::class, OnReservationCancelled::class);
        Event::listen(GuestCheckedIn::class, OnGuestCheckedIn::class);
        Event::listen(GuestCheckedOut::class, OnGuestCheckedOut::class);
    }
}
