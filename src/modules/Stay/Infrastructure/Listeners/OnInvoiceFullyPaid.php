<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Billing\Infrastructure\IntegrationEvent\InvoiceFullyPaidEvent;
use Modules\Stay\Application\Command\ConfirmPaidReservation;
use Modules\Stay\Application\Command\ConfirmPaidReservationHandler;

final readonly class OnInvoiceFullyPaid implements ShouldQueue
{
    public function __construct(
        private ConfirmPaidReservationHandler $handler,
    ) {}

    public function handle(InvoiceFullyPaidEvent $event): void
    {
        $this->handler->handle(new ConfirmPaidReservation(
            reservationId: $event->reservationId,
        ));
    }
}
