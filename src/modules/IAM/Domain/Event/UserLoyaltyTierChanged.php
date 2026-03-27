<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Event;

use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Domain\ValueObject\UserId;
use Modules\Shared\Domain\DomainEvent;

final class UserLoyaltyTierChanged extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly LoyaltyTier $tier,
    ) {
        parent::__construct();
    }
}
