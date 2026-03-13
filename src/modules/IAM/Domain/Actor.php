<?php

declare(strict_types=1);

namespace Modules\IAM\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\Event\ActorRegistered;
use Modules\IAM\Domain\ValueObject\RoleName;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Actor extends AggregateRoot
{
    private function __construct(
        public readonly ActorId $uuid,
        public readonly ?AccountId $accountId,
        /** @var list<Role> */
        public private(set) array $roles,
        public readonly string $name,
        public readonly string $email,
        public private(set) HashedPassword $password,
        public readonly ?string $subjectType,
        public readonly ?int $subjectId,
        public readonly DateTimeImmutable $createdAt,
        public private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * @param list<Role> $roles
     */
    public static function register(
        ActorId $uuid,
        ?AccountId $accountId,
        array $roles,
        string $name,
        string $email,
        HashedPassword $password,
        ?string $subjectType,
        ?int $subjectId,
        DateTimeImmutable $createdAt,
    ): self {
        $actor = new self(
            uuid: $uuid,
            accountId: $accountId,
            roles: $roles,
            name: $name,
            email: $email,
            password: $password,
            subjectType: $subjectType,
            subjectId: $subjectId,
            createdAt: $createdAt,
        );

        $actor->recordEvent(new ActorRegistered($uuid, $accountId, $email));

        return $actor;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::ADMIN);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleName::SUPERADMIN);
    }

    public function hasRole(RoleName $role): bool
    {
        foreach ($this->roles as $r) {
            if ($r->name === $role) {
                return true;
            }
        }
        return false;
    }

    /** @return list<Role> */
    public function roles(): array
    {
        return $this->roles;
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
