<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Stripe;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Billing\Application\Command\HandlePaymentFailed;
use Modules\Billing\Application\Command\HandlePaymentFailedHandler;
use Modules\Billing\Application\Command\HandlePaymentSucceeded;
use Modules\Billing\Application\Command\HandlePaymentSucceededHandler;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

final class StripeWebhookController
{
    public function __construct(
        private readonly HandlePaymentSucceededHandler $succeededHandler,
        private readonly HandlePaymentFailedHandler $failedHandler,
        private readonly InvoiceRepository $invoiceRepository,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');
        $webhookSecret = config('billing.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            return new JsonResponse(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        // Idempotency check
        if ($this->invoiceRepository->hasProcessedStripeEvent($event->id)) {
            return new JsonResponse(['status' => 'already_processed'], 200);
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->succeededHandler->handle(
                new HandlePaymentSucceeded(
                    stripePaymentIntentId: $event->data->object->id,
                ),
            ),
            'payment_intent.payment_failed' => $this->failedHandler->handle(
                new HandlePaymentFailed(
                    stripePaymentIntentId: $event->data->object->id,
                    reason: $event->data->object->last_payment_error?->message ?? 'Unknown error',
                ),
            ),
            default => null,
        };

        // Record processed event
        $this->invoiceRepository->recordStripeEvent($event->id, $event->type);

        return new JsonResponse(['status' => 'ok'], 200);
    }
}
