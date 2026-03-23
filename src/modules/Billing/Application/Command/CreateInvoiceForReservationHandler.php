<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use DateTimeImmutable;
use Modules\Billing\Domain\Invoice;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\LineItem;
use Modules\Billing\Domain\LineItemId;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\ValueObject\Money;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

class CreateInvoiceForReservationHandler extends EventDispatchingHandler
{
    public function __construct(
        private InvoiceRepository $repository,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(CreateInvoiceForReservation $command): InvoiceId
    {
        $id = $this->repository->nextIdentity();

        $unitPriceCents = (int) round($command->pricePerNight * 100);

        $lineItem = LineItem::create(
            id: LineItemId::generate(),
            description: "{$command->stayName} — {$command->nights} night(s) ({$command->checkIn} to {$command->checkOut})",
            unitPrice: new Money($unitPriceCents),
            quantity: $command->nights,
        );

        $invoice = Invoice::createForReservation(
            uuid: $id,
            accountId: $command->accountId,
            reservationId: $command->reservationId,
            guestId: $command->guestId,
            lineItems: [$lineItem],
            taxRate: $command->taxRate,
            createdAt: new DateTimeImmutable(),
        );

        $this->repository->save($invoice);
        $this->dispatchEvents($invoice);

        return $id;
    }
}
