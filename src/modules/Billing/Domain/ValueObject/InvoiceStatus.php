<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\ValueObject;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case PAID = 'paid';
    case VOID = 'void';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ISSUED => 'Issued',
            self::PAID => 'Paid',
            self::VOID => 'Void',
            self::REFUNDED => 'Refunded',
        };
    }
}
