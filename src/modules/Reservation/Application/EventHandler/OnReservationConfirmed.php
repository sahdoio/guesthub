<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\EventHandler;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Reservation\Application\IntegrationEvent\ReservationConfirmedEvent;
use Modules\Reservation\Domain\Event\ReservationConfirmed;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\Repository\ReservationRepository;

final class OnReservationConfirmed
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function handle(ReservationConfirmed $event): void
    {
        $reservation = $this->repository->findByUuid($event->reservationId)
            ?? throw ReservationNotFoundException::withId($event->reservationId);

        $this->dispatcher->dispatch(new ReservationConfirmedEvent(
            reservationId: (string) $event->reservationId,
            guestEmail: (string) $reservation->guest()->email,
            roomType: $reservation->roomType(),
            checkIn: $reservation->period()->checkIn->format('Y-m-d'),
            checkOut: $reservation->period()->checkOut->format('Y-m-d'),
            isVip: $reservation->guest()->isVip,
            occurredAt: $event->occurredOn(),
        ));
    }
}
