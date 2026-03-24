<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Exception;

final class InvoiceNotFoundException extends \DomainException
{
    public static function withId(string|\Stringable $id): self
    {
        return new self("Invoice with ID '{$id}' not found.");
    }
}
