<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Service;

use Modules\Billing\Domain\DTO\PaymentGatewayResult;
use Modules\Billing\Domain\DTO\PaymentIntent;
use Modules\Billing\Domain\ValueObject\Money;

interface PaymentGateway
{
    public function createPaymentIntent(PaymentIntent $intent): PaymentGatewayResult;

    public function refundPayment(string $paymentIntentId, ?Money $amount = null): PaymentGatewayResult;

    public function createCustomer(string $email, string $name): string;
}
