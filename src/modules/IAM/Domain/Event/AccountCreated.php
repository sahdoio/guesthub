<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Event;

use Modules\IAM\Domain\ValueObject\AccountId;
use Modules\Shared\Domain\DomainEvent;

final class AccountCreated extends DomainEvent
{
    public function __construct(
        public readonly AccountId $accountId,
        public readonly string $name,
    ) {
        parent::__construct();
    }
}
