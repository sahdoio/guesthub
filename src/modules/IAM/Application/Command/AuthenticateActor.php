<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

final readonly class AuthenticateActor
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
