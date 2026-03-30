<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use DateTimeImmutable;
use DomainException;
use Modules\Billing\Domain\DTO\PaymentGatewayResult;
use Modules\Billing\Domain\DTO\PaymentIntent;
use Modules\Billing\Domain\Exception\InvoiceNotFoundException;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\PaymentId;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\Service\PaymentGateway;
use Modules\Billing\Domain\ValueObject\PaymentMethod;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

class InitiatePaymentHandler extends EventDispatchingHandler
{
    public function __construct(
        private InvoiceRepository $repository,
        private PaymentGateway $paymentGateway,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(InitiatePayment $command): PaymentGatewayResult
    {
        $id = InvoiceId::fromString($command->invoiceId);
        $invoice = $this->repository->findByUuid($id)
            ?? throw InvoiceNotFoundException::withId($id);

        if ($invoice->stripeCustomerId === null) {
            throw new DomainException('Invoice has no Stripe customer. Ensure the guest has a Stripe customer ID.');
        }

        $result = $this->paymentGateway->createPaymentIntent(new PaymentIntent(
            amount: $invoice->total,
            customerId: $invoice->stripeCustomerId,
            metadata: [
                'invoice_id' => (string) $id,
            ],
        ));

        if ($result->success && $result->paymentIntentId !== null) {
            $invoice->recordPayment(
                paymentId: PaymentId::generate(),
                amount: $invoice->total,
                method: PaymentMethod::from($command->paymentMethod),
                stripePaymentIntentId: $result->paymentIntentId,
                createdAt: new DateTimeImmutable,
            );

            $this->repository->save($invoice);
            $this->dispatchEvents($invoice);
        }

        return $result;
    }
}
