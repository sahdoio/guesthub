<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Event;

use DateTimeImmutable;
use Modules\IAM\Domain\UserId;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\Shared\Domain\DomainEvent;

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
