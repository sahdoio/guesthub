<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\EventHandler;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Reservation\Application\IntegrationEvent\GuestCheckedInEvent;
use Modules\Reservation\Domain\Event\GuestCheckedIn;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\Repository\ReservationRepository;

final class OnGuestCheckedIn
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function handle(GuestCheckedIn $event): void
    {
        $reservation = $this->repository->findByUuid($event->reservationId)
            ?? throw ReservationNotFoundException::withId($event->reservationId);

        $this->dispatcher->dispatch(new GuestCheckedInEvent(
            reservationId: (string) $event->reservationId,
            roomNumber: $event->roomNumber,
            guestEmail: (string) $reservation->guest()->email,
            isVip: $reservation->guest()->isVip,
            occurredAt: $event->occurredOn(),
        ));
    }
}
