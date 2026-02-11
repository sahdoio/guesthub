<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Dto;

final readonly class GuestInfo
{
    public function __construct(
        public string $guestProfileId,
        public string $fullName,
        public string $email,
        public string $phone,
        public string $document,
        public bool $isVip,
    ) {}
}
