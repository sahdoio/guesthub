<?php

declare(strict_types=1);

namespace Modules\IAM\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\Event\ActorRegistered;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Actor extends AggregateRoot
{
    private function __construct(
        public readonly ActorId $uuid,
        public readonly ?AccountId $accountId,
        /** @var list<RoleId> */
        public private(set) array $roleIds,
        public readonly string $name,
        public readonly string $email,
        public private(set) HashedPassword $password,
        public readonly ?string $subjectType,
        public readonly ?int $subjectId,
        public readonly DateTimeImmutable $createdAt,
        public private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * @param  list<RoleId>  $roleIds
     */
    public static function register(
        ActorId $uuid,
        ?AccountId $accountId,
        array $roleIds,
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
            roleIds: $roleIds,
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

    public function hasRoleId(RoleId $roleId): bool
    {
        foreach ($this->roleIds as $id) {
            if ($id->equals($roleId)) {
                return true;
            }
        }

        return false;
    }

    public function assignRole(RoleId $roleId): void
    {
        if (! $this->hasRoleId($roleId)) {
            $this->roleIds[] = $roleId;
            $this->updatedAt = new DateTimeImmutable;
        }
    }

    /** @return list<RoleId> */
    public function roleIds(): array
    {
        return $this->roleIds;
    }

    public function id(): Identity
    {
        return $this->uuid;
    }

    public function changePassword(HashedPassword $password): void
    {
        $this->password = $password;
        $this->updatedAt = new DateTimeImmutable;
    }
}
