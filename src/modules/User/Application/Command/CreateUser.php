<?php

declare(strict_types=1);

namespace Modules\User\Application\Command;

final readonly class CreateUser
{
    public function __construct(
        public string $fullName,
        public string $email,
        public string $phone,
        public string $document,
        public ?string $loyaltyTier = null,
    ) {}
}
