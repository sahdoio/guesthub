<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Listeners;

use Modules\Billing\Application\Command\CreateInvoiceForReservation;
use Modules\Billing\Application\Command\CreateInvoiceForReservationHandler;
use Modules\Billing\Domain\Service\ReservationGateway;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationConfirmedEvent;

final readonly class OnReservationConfirmed
{
    public function __construct(
        private ReservationGateway $reservationGateway,
        private CreateInvoiceForReservationHandler $handler,
    ) {}

    public function handle(ReservationConfirmedEvent $event): void
    {
        $reservation = $this->reservationGateway->findReservation($event->reservationId);

        $taxRate = config('billing.default_tax_rate', 0.0);

        $this->handler->handle(new CreateInvoiceForReservation(
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
    }
}
