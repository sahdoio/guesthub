<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Command;

final readonly class CreateGuestProfile
{
    /**
     * @param string[] $preferences
     */
    public function __construct(
        public string $fullName,
        public string $email,
        public string $phone,
        public string $document,
        public string $loyaltyTier,
        public array $preferences = [],
    ) {}
}
