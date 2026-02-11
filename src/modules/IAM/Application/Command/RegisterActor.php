<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

final readonly class RegisterActor
{
    public function __construct(
        public string $type,
        public string $name,
        public string $email,
        public string $password,
        public ?string $guestProfileId = null,
    ) {}
}
