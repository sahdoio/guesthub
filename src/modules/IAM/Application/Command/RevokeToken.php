<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

final readonly class RevokeToken
{
    public function __construct(
        public string $actorEmail,
    ) {}
}
