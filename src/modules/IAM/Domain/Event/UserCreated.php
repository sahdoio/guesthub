<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Event;

use Modules\IAM\Domain\ValueObject\UserId;
use Modules\Shared\Domain\DomainEvent;

final class UserCreated extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly string $name,
        public readonly string $email,
        public readonly string $hashedPassword,
        public readonly string $actorType,
        public readonly ?string $accountName = null,
        public readonly ?string $accountSlug = null,
    ) {
        parent::__construct();
    }
}
