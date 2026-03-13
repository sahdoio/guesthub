<?php

declare(strict_types=1);

namespace Modules\Guest\Domain\Event;

use DateTimeImmutable;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Shared\Domain\DomainEvent;

final readonly class GuestLoyaltyTierChanged implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public GuestId $guestId,
        public LoyaltyTier $newTier,
    ) {
        $this->occurredOn = new DateTimeImmutable;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
