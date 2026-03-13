<?php

declare(strict_types=1);

namespace Modules\IAM\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\Event\AccountCreated;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Account extends AggregateRoot
{
    private function __construct(
        public readonly AccountId $uuid,
        public readonly string $name,
        public readonly DateTimeImmutable $createdAt,
        public private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    public static function create(
        AccountId $uuid,
        string $name,
        DateTimeImmutable $createdAt,
    ): self {
        $account = new self(
            uuid: $uuid,
            name: $name,
            createdAt: $createdAt,
        );

        $account->recordEvent(new AccountCreated($uuid, $name));

        return $account;
    }

    public function id(): Identity
    {
        return $this->uuid;
    }
}
