<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Exception;

final class PaymentNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Payment with ID '{$id}' not found.");
    }
}
