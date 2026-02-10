<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\EventHandler;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Reservation\Application\IntegrationEvent\GuestCheckedOutEvent;
use Modules\Reservation\Domain\Event\GuestCheckedOut;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\Repository\ReservationRepository;

final class OnGuestCheckedOut
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function handle(GuestCheckedOut $event): void
    {
        $reservation = $this->repository->findByUuid($event->reservationId)
            ?? throw ReservationNotFoundException::withId($event->reservationId);

        $this->dispatcher->dispatch(new GuestCheckedOutEvent(
            reservationId: (string) $event->reservationId,
            roomNumber: $reservation->assignedRoomNumber() ?? '',
            guestEmail: (string) $reservation->guest()->email,
            occurredAt: $event->occurredOn(),
        ));
    }
}
