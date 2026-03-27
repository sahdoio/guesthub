<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use DateTimeImmutable;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\ValueObject\InvoiceStatus;

final readonly class CancelReservationBillingHandler
{
    public function __construct(
        private InvoiceRepository $repository,
        private VoidInvoiceHandler $voidHandler,
        private RefundInvoiceHandler $refundHandler,
    ) {}

    public function handle(CancelReservationBilling $command): void
    {
        $invoice = $this->repository->findByReservationId($command->reservationId);

        if ($invoice === null) {
            return;
        }

        $invoiceId = (string) $invoice->uuid;

        if (in_array($invoice->status, [InvoiceStatus::DRAFT, InvoiceStatus::ISSUED], true)) {
            $this->voidHandler->handle(new VoidInvoice(
                invoiceId: $invoiceId,
                reason: "Reservation cancelled: {$command->reason}",
            ));

            return;
        }

        if ($invoice->status === InvoiceStatus::PAID) {
            $withinWindow = $command->freeCancellationUntil !== null
                && new DateTimeImmutable < new DateTimeImmutable($command->freeCancellationUntil);

            if ($withinWindow) {
                $this->refundHandler->handle(new RefundInvoice(
                    invoiceId: $invoiceId,
                ));
            }
        }
    }
}
