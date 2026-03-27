<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Exception;

final class AccountNotFoundException extends \RuntimeException
{
    public static function withUuid(string $uuid): self
    {
        return new self("Account not found: {$uuid}");
    }
}
