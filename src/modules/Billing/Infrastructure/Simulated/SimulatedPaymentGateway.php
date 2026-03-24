<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Simulated;

use Modules\Billing\Domain\DTO\PaymentGatewayResult;
use Modules\Billing\Domain\DTO\PaymentIntent;
use Modules\Billing\Domain\Service\PaymentGateway;
use Modules\Billing\Domain\ValueObject\Money;

final class SimulatedPaymentGateway implements PaymentGateway
{
    public function createPaymentIntent(PaymentIntent $intent): PaymentGatewayResult
    {
        return new PaymentGatewayResult(
            success: true,
            paymentIntentId: 'sim_'.bin2hex(random_bytes(12)),
            clientSecret: null,
            errorMessage: null,
        );
    }

    public function refundPayment(string $paymentIntentId, ?Money $amount = null): PaymentGatewayResult
    {
        return new PaymentGatewayResult(
            success: true,
            paymentIntentId: $paymentIntentId,
            clientSecret: null,
            errorMessage: null,
        );
    }

    public function createCustomer(string $email, string $name): string
    {
        return 'sim_cus_'.bin2hex(random_bytes(8));
    }
}
