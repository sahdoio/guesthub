<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Listeners;

use Modules\Shared\Application\EventDispatcher;
use Modules\Reservation\Infrastructure\IntegrationEvent\ReservationCancelledEvent;
use Modules\Reservation\Domain\Event\ReservationCancelled;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\Repository\ReservationRepository;

final class OnReservationCancelled
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly EventDispatcher $dispatcher,
    ) {}

    public function handle(ReservationCancelled $event): void
    {
        $reservation = $this->repository->findByUuid($event->reservationId)
            ?? throw ReservationNotFoundException::withId($event->reservationId);

        $this->dispatcher->dispatch(new ReservationCancelledEvent(
            reservationId: (string) $event->reservationId,
            roomType: $reservation->roomType,
            checkIn: $reservation->period->checkIn->format('Y-m-d'),
            checkOut: $reservation->period->checkOut->format('Y-m-d'),
            reason: $event->reason,
            occurredAt: $event->occurredOn(),
        ));
    }
}
