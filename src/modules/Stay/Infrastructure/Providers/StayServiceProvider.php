<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Billing\Infrastructure\IntegrationEvent\InvoiceFullyPaidEvent;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Infrastructure\Messaging\LaravelEventDispatcher;
use Modules\Stay\Application\Listeners\OnGuestCheckedIn;
use Modules\Stay\Application\Listeners\OnGuestCheckedOut;
use Modules\Stay\Application\Listeners\OnInvoiceFullyPaid;
use Modules\Stay\Application\Listeners\OnReservationCancelled;
use Modules\Stay\Application\Listeners\OnReservationConfirmed;
use Modules\Stay\Application\Listeners\OnReservationCreated;
use Modules\Stay\Domain\Event\GuestCheckedIn;
use Modules\Stay\Domain\Event\GuestCheckedOut;
use Modules\Stay\Domain\Event\ReservationCancelled;
use Modules\Stay\Domain\Event\ReservationConfirmed;
use Modules\Stay\Domain\Event\ReservationCreated;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\Service\GuestGateway;
use Modules\Stay\Infrastructure\Integration\GuestGatewayAdapter;
use Modules\Stay\Infrastructure\Persistence\Eloquent\EloquentReservationRepository;
use Modules\Stay\Infrastructure\Persistence\Eloquent\EloquentStayRepository;

final class StayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Stay bindings
        $this->app->bind(StayRepository::class, EloquentStayRepository::class);

        // Reservation bindings
        $this->mergeConfigFrom(__DIR__.'/../Config/reservation.php', 'reservation');
        $this->app->bind(ReservationRepository::class, EloquentReservationRepository::class);
        $this->app->bind(GuestGateway::class, GuestGatewayAdapter::class);
        $this->app->bindIf(EventDispatcher::class, LaravelEventDispatcher::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');

        Route::middleware('web')
            ->group(__DIR__.'/../Routes/web.php');

        // Reservation event listeners
        Event::listen(ReservationCreated::class, OnReservationCreated::class);
        Event::listen(ReservationConfirmed::class, OnReservationConfirmed::class);
        Event::listen(ReservationCancelled::class, OnReservationCancelled::class);
        Event::listen(GuestCheckedIn::class, OnGuestCheckedIn::class);
        Event::listen(GuestCheckedOut::class, OnGuestCheckedOut::class);
        Event::listen(InvoiceFullyPaidEvent::class, OnInvoiceFullyPaid::class);
    }
}
