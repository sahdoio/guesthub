<?php

declare(strict_types=1);

namespace Modules\User\Domain\Event;

use DateTimeImmutable;
use Modules\Shared\Domain\DomainEvent;
use Modules\User\Domain\UserId;
use Modules\User\Domain\ValueObject\LoyaltyTier;

final readonly class UserLoyaltyTierChanged implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public UserId $userId,
        public LoyaltyTier $tier,
    ) {
        $this->occurredOn = new DateTimeImmutable;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
