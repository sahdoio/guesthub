<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Listeners;

use Modules\Shared\Application\EventDispatcher;
use Modules\Reservation\Infrastructure\IntegrationEvent\ReservationConfirmedEvent;
use Modules\Reservation\Domain\Event\ReservationConfirmed;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Service\GuestGateway;

final readonly class OnReservationConfirmed
{
    public function __construct(
        private ReservationRepository $repository,
        private EventDispatcher $dispatcher,
        private GuestGateway $guestGateway,
    ) {}

    public function handle(ReservationConfirmed $event): void
    {
        $reservation = $this->repository->findByUuid($event->reservationId)
            ?? throw ReservationNotFoundException::withId($event->reservationId);

        $guestInfo = $this->guestGateway->findByUuid($reservation->guestProfileId);

        $this->dispatcher->dispatch(new ReservationConfirmedEvent(
            reservationId: (string) $event->reservationId,
            guestEmail: $guestInfo?->email ?? '',
            roomType: $reservation->roomType,
            checkIn: $reservation->period->checkIn->format('Y-m-d'),
            checkOut: $reservation->period->checkOut->format('Y-m-d'),
            isVip: $guestInfo?->isVip ?? false,
            occurredAt: $event->occurredOn(),
        ));
    }
}
