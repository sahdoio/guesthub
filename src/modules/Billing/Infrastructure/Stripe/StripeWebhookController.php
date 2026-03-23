<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Stripe;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Billing\Application\Command\HandlePaymentFailed;
use Modules\Billing\Application\Command\HandlePaymentFailedHandler;
use Modules\Billing\Application\Command\HandlePaymentSucceeded;
use Modules\Billing\Application\Command\HandlePaymentSucceededHandler;
use Modules\Billing\Infrastructure\Persistence\Eloquent\StripeWebhookEventModel;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

final class StripeWebhookController
{
    public function __construct(
        private readonly HandlePaymentSucceededHandler $succeededHandler,
        private readonly HandlePaymentFailedHandler $failedHandler,
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
        $existing = StripeWebhookEventModel::query()
            ->where('stripe_event_id', $event->id)
            ->exists();

        if ($existing) {
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
        StripeWebhookEventModel::query()->create([
            'stripe_event_id' => $event->id,
            'event_type' => $event->type,
            'processed_at' => now()->format('Y-m-d H:i:s'),
        ]);

        return new JsonResponse(['status' => 'ok'], 200);
    }
}
