<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Exception;

final class ActorAlreadyExistsException extends \DomainException
{
    public static function withEmail(string $email): self
    {
        return new self("An actor with email '{$email}' already exists.");
    }
}
