<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\DTO;

use Modules\Billing\Domain\ValueObject\Money;

final readonly class PaymentIntent
{
    /**
     * @param  array<string, string>  $metadata
     */
    public function __construct(
        public Money $amount,
        public ?string $customerId = null,
        public array $metadata = [],
    ) {}
}
