<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Listeners;

use Modules\Billing\Application\Command\IssueInvoice;
use Modules\Billing\Application\Command\IssueInvoiceHandler;
use Modules\Billing\Domain\ValueObject\InvoiceStatus;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Stay\Infrastructure\IntegrationEvent\GuestCheckedOutEvent;

final readonly class OnGuestCheckedOut
{
    public function __construct(
        private InvoiceRepository $repository,
        private IssueInvoiceHandler $issueHandler,
    ) {}

    public function handle(GuestCheckedOutEvent $event): void
    {
        $invoice = $this->repository->findByReservationId($event->reservationId);

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
