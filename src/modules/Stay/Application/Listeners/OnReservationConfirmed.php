<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Listeners;

use Modules\Stay\Domain\Event\ReservationConfirmed;
use Modules\Stay\Domain\Exception\ReservationNotFoundException;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Service\GuestGateway;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationConfirmedEvent;
use Modules\Shared\Application\EventDispatcher;

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

        $guestInfo = $this->guestGateway->findByUuid($reservation->guestId);

        $this->dispatcher->dispatch(new ReservationConfirmedEvent(
            reservationId: (string) $event->reservationId,
            guestEmail: $guestInfo->email ?? '',
            stayId: $reservation->stayId,
            checkIn: $reservation->period->checkIn->format('Y-m-d'),
            checkOut: $reservation->period->checkOut->format('Y-m-d'),
            isVip: $guestInfo->isVip ?? false,
            occurredAt: $event->occurredOn(),
        ));
    }
}
