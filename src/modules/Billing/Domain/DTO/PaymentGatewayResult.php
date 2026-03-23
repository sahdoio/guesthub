<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\DTO;

final readonly class PaymentGatewayResult
{
    public function __construct(
        public bool $success,
        public ?string $paymentIntentId,
        public ?string $clientSecret,
        public ?string $errorMessage,
    ) {}
}
