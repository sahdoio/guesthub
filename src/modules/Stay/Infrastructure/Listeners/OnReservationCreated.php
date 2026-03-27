<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Listeners;

use Modules\Stay\Application\Command\ProcessNewReservation;
use Modules\Stay\Application\Command\ProcessNewReservationHandler;
use Modules\Stay\Domain\Event\ReservationCreated;

final readonly class OnReservationCreated
{
    public function __construct(
        private ProcessNewReservationHandler $handler,
    ) {}

    public function handle(ReservationCreated $event): void
    {
        $this->handler->handle(new ProcessNewReservation(
            reservationId: (string) $event->reservationId,
        ));
    }
}
