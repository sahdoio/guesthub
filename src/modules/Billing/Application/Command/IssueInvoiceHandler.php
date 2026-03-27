<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use Modules\Billing\Domain\Exception\InvoiceNotFoundException;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\Service\AccountGateway;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

class IssueInvoiceHandler extends EventDispatchingHandler
{
    public function __construct(
        private InvoiceRepository $repository,
        private AccountGateway $accountGateway,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(IssueInvoice $command): void
    {
        $id = InvoiceId::fromString($command->invoiceId);
        $invoice = $this->repository->findByUuid($id)
            ?? throw InvoiceNotFoundException::withId($id);

        $invoice->issue();

        $this->repository->save($invoice, $this->accountGateway->resolveNumericId($invoice->accountId));
        $this->dispatchEvents($invoice);
    }
}
