<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Listeners;

use Modules\Billing\Application\Command\CreateInvoiceForReservation;
use Modules\Billing\Application\Command\CreateInvoiceForReservationHandler;
use Modules\Billing\Application\Command\IssueInvoice;
use Modules\Billing\Application\Command\IssueInvoiceHandler;
use Modules\Billing\Domain\Service\ReservationGateway;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationCreatedEvent;

final readonly class OnReservationCreated
{
    public function __construct(
        private ReservationGateway $reservationGateway,
        private CreateInvoiceForReservationHandler $createHandler,
        private IssueInvoiceHandler $issueHandler,
    ) {}

    public function handle(ReservationCreatedEvent $event): void
    {
        $reservation = $this->reservationGateway->findReservation($event->reservationId);

        $taxRate = config('billing.default_tax_rate', 0.0);

        $invoiceId = $this->createHandler->handle(new CreateInvoiceForReservation(
            reservationId: $event->reservationId,
            accountId: $reservation->accountId,
            guestId: $reservation->guestId,
            stayName: $reservation->stayName,
            pricePerNight: $reservation->pricePerNight,
            nights: $reservation->nights,
            checkIn: $event->checkIn,
            checkOut: $event->checkOut,
            taxRate: (float) $taxRate,
        ));

        // Immediately issue so guest can pay
        $this->issueHandler->handle(new IssueInvoice(
            invoiceId: (string) $invoiceId,
        ));
    }
}
