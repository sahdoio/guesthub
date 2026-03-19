<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Integration\Dto;

final readonly class UserData
{
    public function __construct(
        public string $uuid,
        public string $fullName,
        public string $email,
        public string $phone,
        public string $document,
        public ?string $loyaltyTier,
    ) {}
}
