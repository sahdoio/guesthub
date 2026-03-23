<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use Modules\Billing\Domain\Exception\InvoiceNotFoundException;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

class VoidInvoiceHandler extends EventDispatchingHandler
{
    public function __construct(
        private InvoiceRepository $repository,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(VoidInvoice $command): void
    {
        $id = InvoiceId::fromString($command->invoiceId);
        $invoice = $this->repository->findByUuid($id)
            ?? throw InvoiceNotFoundException::withId($id);

        $invoice->void($command->reason);

        $this->repository->save($invoice);
        $this->dispatchEvents($invoice);
    }
}
