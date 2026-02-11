<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Exception;

final class InvalidCredentialsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}
