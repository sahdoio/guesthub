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
        public private(set) string $name,
        public private(set) string $slug,
        public private(set) string $status,
        public readonly DateTimeImmutable $createdAt,
        public private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    public static function create(
        AccountId $uuid,
        string $name,
        string $slug,
        DateTimeImmutable $createdAt,
    ): self {
        $account = new self(
            uuid: $uuid,
            name: $name,
            slug: $slug,
            status: 'active',
            createdAt: $createdAt,
        );

        $account->recordEvent(new AccountCreated($uuid, $name));

        return $account;
    }

    public function updateProfile(
        string $name,
        string $slug,
    ): void {
        $this->name = $name;
        $this->slug = $slug;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function suspend(): void
    {
        $this->status = 'suspended';
        $this->updatedAt = new DateTimeImmutable;
    }

    public function activate(): void
    {
        $this->status = 'active';
        $this->updatedAt = new DateTimeImmutable;
    }

    public function id(): Identity
    {
        return $this->uuid;
    }
}
