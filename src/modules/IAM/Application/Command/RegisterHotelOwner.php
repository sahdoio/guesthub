<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

final readonly class RegisterHotelOwner
{
    public function __construct(
        public string $ownerName,
        public string $email,
        public string $password,
        public string $phone,
        public string $document,
        public string $accountName,
        public string $accountSlug,
    ) {}
}
