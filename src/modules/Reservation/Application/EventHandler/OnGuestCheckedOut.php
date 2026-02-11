<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\EventHandler;

use Modules\Shared\Application\EventDispatcher;
use Modules\Reservation\Infrastructure\IntegrationEvent\GuestCheckedOutEvent;
use Modules\Reservation\Domain\Event\GuestCheckedOut;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Service\GuestGateway;

final class OnGuestCheckedOut
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly EventDispatcher $dispatcher,
        private readonly GuestGateway $guestGateway,
    ) {}

    public function handle(GuestCheckedOut $event): void
    {
        $reservation = $this->repository->findByUuid($event->reservationId)
            ?? throw ReservationNotFoundException::withId($event->reservationId);

        $guestInfo = $this->guestGateway->findByUuid($reservation->guestProfileId());

        $this->dispatcher->dispatch(new GuestCheckedOutEvent(
            reservationId: (string) $event->reservationId,
            roomNumber: $reservation->assignedRoomNumber() ?? '',
            guestEmail: $guestInfo?->email ?? '',
            occurredAt: $event->occurredOn(),
        ));
    }
}
