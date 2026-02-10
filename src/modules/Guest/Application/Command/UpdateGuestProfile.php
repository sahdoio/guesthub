<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Command;

final readonly class UpdateGuestProfile
{
    /**
     * @param string[]|null $preferences
     */
    public function __construct(
        public string $guestProfileId,
        public ?string $fullName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $loyaltyTier = null,
        public ?array $preferences = null,
    ) {}
}
