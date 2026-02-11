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
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        private readonly ActorId $uuid,
        private readonly ActorType $type,
        private readonly string $name,
        private readonly string $email,
        private HashedPassword $password,
        private readonly ?string $guestProfileId,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public function id(): Identity
    {
        return $this->uuid;
    }

    public function uuid(): ActorId
    {
        return $this->uuid;
    }

    public function type(): ActorType
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }

    public function guestProfileId(): ?string
    {
        return $this->guestProfileId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changePassword(HashedPassword $password): void
    {
        $this->password = $password;
        $this->updatedAt = new DateTimeImmutable();
    }
}
