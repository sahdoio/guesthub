<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Stripe;

use Modules\Billing\Domain\DTO\PaymentGatewayResult;
use Modules\Billing\Domain\DTO\PaymentIntent;
use Modules\Billing\Domain\Service\PaymentGateway;
use Modules\Billing\Domain\ValueObject\Money;
use Stripe\StripeClient;

final class StripePaymentGateway implements PaymentGateway
{
    private ?StripeClient $stripe = null;

    private function client(): StripeClient
    {
        if ($this->stripe === null) {
            $key = config('billing.stripe.secret_key');
            if (empty($key)) {
                throw new \RuntimeException('Stripe secret key is not configured.');
            }
            $this->stripe = new StripeClient($key);
        }

        return $this->stripe;
    }

    public function createPaymentIntent(PaymentIntent $intent): PaymentGatewayResult
    {
        try {
            $params = [
                'amount' => $intent->amount->amountInCents,
                'currency' => $intent->amount->currency,
                'metadata' => $intent->metadata,
                'automatic_payment_methods' => ['enabled' => true],
            ];

            if ($intent->customerId !== null) {
                $params['customer'] = $intent->customerId;
            }

            $paymentIntent = $this->client()->paymentIntents->create($params);

            return new PaymentGatewayResult(
                success: true,
                paymentIntentId: $paymentIntent->id,
                clientSecret: $paymentIntent->client_secret,
                errorMessage: null,
            );
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new PaymentGatewayResult(
                success: false,
                paymentIntentId: null,
                clientSecret: null,
                errorMessage: $e->getMessage(),
            );
        }
    }

    public function refundPayment(string $paymentIntentId, ?Money $amount = null): PaymentGatewayResult
    {
        try {
            $params = ['payment_intent' => $paymentIntentId];

            if ($amount !== null) {
                $params['amount'] = $amount->amountInCents;
            }

            $refund = $this->client()->refunds->create($params);

            return new PaymentGatewayResult(
                success: true,
                paymentIntentId: $paymentIntentId,
                clientSecret: null,
                errorMessage: null,
            );
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new PaymentGatewayResult(
                success: false,
                paymentIntentId: $paymentIntentId,
                clientSecret: null,
                errorMessage: $e->getMessage(),
            );
        }
    }

    public function createCustomer(string $email, string $name): string
    {
        $customer = $this->client()->customers->create([
            'email' => $email,
            'name' => $name,
        ]);

        return $customer->id;
    }
}
