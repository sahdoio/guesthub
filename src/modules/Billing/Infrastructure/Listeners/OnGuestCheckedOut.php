<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Billing\Application\Command\IssueInvoiceOnCheckout;
use Modules\Billing\Application\Command\IssueInvoiceOnCheckoutHandler;
use Modules\Stay\Infrastructure\IntegrationEvent\GuestCheckedOutEvent;

final readonly class OnGuestCheckedOut implements ShouldQueue
{
    public function __construct(
        private IssueInvoiceOnCheckoutHandler $handler,
    ) {}

    public function handle(GuestCheckedOutEvent $event): void
    {
        $this->handler->handle(new IssueInvoiceOnCheckout(
            reservationId: $event->reservationId,
        ));
    }
}
