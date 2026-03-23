<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use DomainException;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

final class HandlePaymentFailedHandler extends EventDispatchingHandler
{
    public function __construct(
        private InvoiceRepository $repository,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(HandlePaymentFailed $command): void
    {
        $invoice = $this->repository->findByStripePaymentIntentId($command->stripePaymentIntentId)
            ?? throw new DomainException("Invoice not found for Stripe payment intent '{$command->stripePaymentIntentId}'.");

        $invoice->markPaymentFailed($command->stripePaymentIntentId, $command->reason);

        $this->repository->save($invoice);
        $this->dispatchEvents($invoice);
    }
}
