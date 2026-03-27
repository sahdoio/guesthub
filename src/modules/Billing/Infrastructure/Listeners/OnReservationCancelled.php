<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Billing\Application\Command\CancelReservationBilling;
use Modules\Billing\Application\Command\CancelReservationBillingHandler;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationCancelledEvent;

final readonly class OnReservationCancelled implements ShouldQueue
{
    public function __construct(
        private CancelReservationBillingHandler $handler,
    ) {}

    public function handle(ReservationCancelledEvent $event): void
    {
        $this->handler->handle(new CancelReservationBilling(
            reservationId: $event->reservationId,
            reason: $event->reason,
            freeCancellationUntil: $event->freeCancellationUntil,
        ));
    }
}
