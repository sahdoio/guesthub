<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Listeners;

use Modules\Billing\Domain\Event\InvoiceFullyPaid;
use Modules\Billing\Infrastructure\IntegrationEvent\InvoiceFullyPaidEvent;
use Modules\Shared\Application\EventDispatcher;

final readonly class OnInvoiceFullyPaid
{
    public function __construct(
        private EventDispatcher $dispatcher,
    ) {}

    public function handle(InvoiceFullyPaid $event): void
    {
        $this->dispatcher->dispatch(new InvoiceFullyPaidEvent(
            invoiceId: (string) $event->invoiceId,
            reservationId: $event->reservationId,
            occurredAt: $event->occurredOn(),
        ));
    }
}
