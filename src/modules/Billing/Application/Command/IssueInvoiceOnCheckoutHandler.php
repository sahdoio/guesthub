<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\ValueObject\InvoiceStatus;

final readonly class IssueInvoiceOnCheckoutHandler
{
    public function __construct(
        private InvoiceRepository $repository,
        private IssueInvoiceHandler $issueHandler,
    ) {}

    public function handle(IssueInvoiceOnCheckout $command): void
    {
        $invoice = $this->repository->findByReservationId($command->reservationId);

        if ($invoice === null) {
            return;
        }

        if ($invoice->status === InvoiceStatus::DRAFT) {
            $this->issueHandler->handle(new IssueInvoice(
                invoiceId: (string) $invoice->uuid,
            ));
        }
    }
}
