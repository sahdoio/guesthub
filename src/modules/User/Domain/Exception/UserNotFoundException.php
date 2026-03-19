<?php

declare(strict_types=1);

namespace Modules\User\Domain\Exception;

use RuntimeException;

final class UserNotFoundException extends RuntimeException
{
    public static function withUuid(string $uuid): self
    {
        return new self("User with UUID {$uuid} not found.");
    }
}
