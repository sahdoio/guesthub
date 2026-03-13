<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Listeners;

use Modules\Reservation\Domain\Event\GuestCheckedIn;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Service\GuestGateway;
use Modules\Reservation\Infrastructure\IntegrationEvent\GuestCheckedInEvent;
use Modules\Shared\Application\EventDispatcher;

final readonly class OnGuestCheckedIn
{
    public function __construct(
        private ReservationRepository $repository,
        private EventDispatcher $dispatcher,
        private GuestGateway $guestGateway,
    ) {}

    public function handle(GuestCheckedIn $event): void
    {
        $reservation = $this->repository->findByUuid($event->reservationId)
            ?? throw ReservationNotFoundException::withId($event->reservationId);

        $guestInfo = $this->guestGateway->findByUuid($reservation->guestId);

        $this->dispatcher->dispatch(new GuestCheckedInEvent(
            reservationId: (string) $event->reservationId,
            roomNumber: $event->roomNumber,
            guestEmail: $guestInfo->email ?? '',
            isVip: $guestInfo->isVip ?? false,
            occurredAt: $event->occurredOn(),
        ));
    }
}
