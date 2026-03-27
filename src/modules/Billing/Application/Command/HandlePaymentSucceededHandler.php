<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use DomainException;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\Service\AccountGateway;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

final class HandlePaymentSucceededHandler extends EventDispatchingHandler
{
    public function __construct(
        private InvoiceRepository $repository,
        private AccountGateway $accountGateway,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(HandlePaymentSucceeded $command): void
    {
        $invoice = $this->repository->findByStripePaymentIntentId($command->stripePaymentIntentId)
            ?? throw new DomainException("Invoice not found for Stripe payment intent '{$command->stripePaymentIntentId}'.");

        $invoice->markPaymentSucceeded($command->stripePaymentIntentId);

        $this->repository->save($invoice, $this->accountGateway->resolveNumericId($invoice->accountId));
        $this->dispatchEvents($invoice);
    }
}
