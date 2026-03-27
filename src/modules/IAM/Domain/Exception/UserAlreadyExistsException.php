<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Exception;

final class UserAlreadyExistsException extends \DomainException
{
    public static function withEmail(string $email): self
    {
        return new self("A user with email '{$email}' already exists.");
    }
}
