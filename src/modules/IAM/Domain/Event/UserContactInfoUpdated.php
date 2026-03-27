<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Event;

use Modules\IAM\Domain\ValueObject\UserId;
use Modules\Shared\Domain\DomainEvent;

final class UserContactInfoUpdated extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
    ) {
        parent::__construct();
    }
}
