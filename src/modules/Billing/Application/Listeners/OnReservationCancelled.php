<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Listeners;

use Modules\Billing\Application\Command\RefundInvoice;
use Modules\Billing\Application\Command\RefundInvoiceHandler;
use Modules\Billing\Application\Command\VoidInvoice;
use Modules\Billing\Application\Command\VoidInvoiceHandler;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\ValueObject\InvoiceStatus;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationCancelledEvent;

final readonly class OnReservationCancelled
{
    public function __construct(
        private InvoiceRepository $repository,
        private VoidInvoiceHandler $voidHandler,
        private RefundInvoiceHandler $refundHandler,
    ) {}

    public function handle(ReservationCancelledEvent $event): void
    {
        $invoice = $this->repository->findByReservationId($event->reservationId);

        if ($invoice === null) {
            return;
        }

        $invoiceId = (string) $invoice->uuid;

        if (in_array($invoice->status, [InvoiceStatus::DRAFT, InvoiceStatus::ISSUED], true)) {
            $this->voidHandler->handle(new VoidInvoice(
                invoiceId: $invoiceId,
                reason: "Reservation cancelled: {$event->reason}",
            ));

            return;
        }

        if ($invoice->status === InvoiceStatus::PAID) {
            $withinWindow = $event->freeCancellationUntil !== null
                && new \DateTimeImmutable < new \DateTimeImmutable($event->freeCancellationUntil);

            if ($withinWindow) {
                $this->refundHandler->handle(new RefundInvoice(
                    invoiceId: $invoiceId,
                ));
            }
            // If past window, no refund
        }
    }
}
