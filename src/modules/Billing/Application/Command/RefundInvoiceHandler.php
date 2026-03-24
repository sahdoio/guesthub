<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use Modules\Billing\Domain\Exception\InvoiceNotFoundException;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\Service\PaymentGateway;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

class RefundInvoiceHandler extends EventDispatchingHandler
{
    public function __construct(
        private InvoiceRepository $repository,
        private PaymentGateway $paymentGateway,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(RefundInvoice $command): void
    {
        $id = InvoiceId::fromString($command->invoiceId);
        $invoice = $this->repository->findByUuid($id)
            ?? throw InvoiceNotFoundException::withId($id);

        foreach ($invoice->succeededPayments() as $payment) {
            $this->paymentGateway->refundPayment(paymentIntentId: $payment->stripePaymentIntentId);
        }

        $invoice->refund();

        $this->repository->save($invoice);
        $this->dispatchEvents($invoice);
    }
}
