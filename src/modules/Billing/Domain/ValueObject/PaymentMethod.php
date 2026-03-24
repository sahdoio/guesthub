<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\ValueObject;

enum PaymentMethod: string
{
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Card',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::OTHER => 'Other',
        };
    }
}
