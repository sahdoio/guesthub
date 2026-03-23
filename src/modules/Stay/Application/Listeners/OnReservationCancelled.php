<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Listeners;

use Modules\Stay\Domain\Event\ReservationCancelled;
use Modules\Stay\Domain\Exception\ReservationNotFoundException;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationCancelledEvent;
use Modules\Shared\Application\EventDispatcher;

final readonly class OnReservationCancelled
{
    public function __construct(
        private ReservationRepository $repository,
        private EventDispatcher $dispatcher,
    ) {}

    public function handle(ReservationCancelled $event): void
    {
        $reservation = $this->repository->findByUuid($event->reservationId)
            ?? throw ReservationNotFoundException::withId($event->reservationId);

        $this->dispatcher->dispatch(new ReservationCancelledEvent(
            reservationId: (string) $event->reservationId,
            stayId: $reservation->stayId,
            checkIn: $reservation->period->checkIn->format('Y-m-d'),
            checkOut: $reservation->period->checkOut->format('Y-m-d'),
            reason: $event->reason,
            freeCancellationUntil: $reservation->freeCancellationUntil?->format('c'),
            occurredAt: $event->occurredOn(),
        ));
    }
}
