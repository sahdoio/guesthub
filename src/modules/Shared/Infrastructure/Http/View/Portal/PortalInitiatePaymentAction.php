<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Billing\Domain\DTO\PaymentIntent;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\PaymentId;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\Service\AccountGateway;
use Modules\Billing\Domain\Service\PaymentGateway;
use Modules\Billing\Domain\ValueObject\PaymentMethod;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class PortalInitiatePaymentAction extends EventDispatchingHandler
{
    public function __construct(
        private InvoiceRepository $repository,
        private AccountGateway $accountGateway,
        private PaymentGateway $paymentGateway,
        private TenantContext $tenantContext,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $id = InvoiceId::fromString($uuid);

        // Resolve tenant from invoice
        $accountNumericId = $this->repository->resolveAccountNumericId($id);

        if ($accountNumericId === null) {
            abort(404, 'Invoice not found.');
        }

        // Set tenant context so repository can find the invoice
        $this->tenantContext->set($accountNumericId);

        $invoice = $this->repository->findByUuid($id);

        if (! $invoice) {
            abort(404, 'Invoice not found.');
        }

        // Enforce ownership
        $guestUuid = $request->attributes->get('guest_uuid');
        if ($guestUuid && $invoice->guestId !== $guestUuid) {
            abort(403, 'Access denied.');
        }

        $result = $this->paymentGateway->createPaymentIntent(new PaymentIntent(
            amount: $invoice->total,
            metadata: ['invoice_id' => (string) $id],
        ));

        if (! $result->success || $result->paymentIntentId === null) {
            return new JsonResponse(['error' => $result->errorMessage], 422);
        }

        $invoice->recordPayment(
            paymentId: PaymentId::generate(),
            amount: $invoice->total,
            method: PaymentMethod::CARD,
            stripePaymentIntentId: $result->paymentIntentId,
            createdAt: new DateTimeImmutable,
        );

        // Simulated gateway payments succeed immediately
        if ($result->clientSecret === null) {
            $invoice->markPaymentSucceeded($result->paymentIntentId);
        }

        $this->repository->save($invoice, $this->accountGateway->resolveNumericId($invoice->accountId));
        $this->dispatchEvents($invoice);

        return new JsonResponse([
            'client_secret' => $result->clientSecret,
            'payment_intent_id' => $result->paymentIntentId,
            'simulated' => $result->clientSecret === null,
        ]);
    }
}
