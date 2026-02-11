<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Exception;

final class ActorNotFoundException extends \DomainException
{
    public static function withEmail(string $email): self
    {
        return new self("Actor with email '{$email}' not found.");
    }
}
