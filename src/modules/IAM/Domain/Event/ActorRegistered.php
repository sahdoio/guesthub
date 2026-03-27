<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Event;

use Modules\IAM\Domain\ValueObject\AccountId;
use Modules\IAM\Domain\ValueObject\ActorId;
use Modules\Shared\Domain\DomainEvent;

final class ActorRegistered extends DomainEvent
{
    public function __construct(
        public readonly ActorId $actorId,
        public readonly ?AccountId $accountId,
        public readonly string $email,
    ) {
        parent::__construct();
    }
}
