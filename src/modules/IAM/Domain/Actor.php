<?php

declare(strict_types=1);

namespace Modules\IAM\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\ValueObject\ActorType;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Actor extends AggregateRoot
{
    private function __construct(
        public readonly ActorId $uuid,
        public readonly ActorType $type,
        public readonly string $name,
        public readonly string $email,
        public private(set) HashedPassword $password,
        public readonly ?string $profileType,
        public readonly ?string $profileId,
        public readonly DateTimeImmutable $createdAt,
        public private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    public static function register(
        ActorId $uuid,
        ActorType $type,
        string $name,
        string $email,
        HashedPassword $password,
        ?string $profileType,
        ?string $profileId,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            uuid: $uuid,
            type: $type,
            name: $name,
            email: $email,
            password: $password,
            profileType: $profileType,
            profileId: $profileId,
            createdAt: $createdAt,
        );
    }

    public function id(): Identity
    {
        return $this->uuid;
    }

    public function changePassword(HashedPassword $password): void
    {
        $this->password = $password;
        $this->updatedAt = new DateTimeImmutable();
    }
}
