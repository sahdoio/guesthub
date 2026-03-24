<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\ValueObject;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SUCCEEDED => 'Succeeded',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }
}
