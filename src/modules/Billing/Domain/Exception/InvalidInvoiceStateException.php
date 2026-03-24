<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Exception;

use Modules\Billing\Domain\ValueObject\InvoiceStatus;

final class InvalidInvoiceStateException extends \DomainException
{
    public static function forTransition(InvoiceStatus $from, InvoiceStatus $to): self
    {
        return new self("Cannot transition invoice from '{$from->value}' to '{$to->value}'.");
    }
}
