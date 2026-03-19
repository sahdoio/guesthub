<?php

declare(strict_types=1);

namespace Modules\IAM\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\Event\ActorRegistered;
use Modules\IAM\Domain\Exception\ActorAlreadyExistsException;
use Modules\IAM\Domain\Service\EmailUniquenessChecker;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Actor extends AggregateRoot
{
    private function __construct(
        public readonly ActorId $uuid,
        public readonly ?AccountId $accountId,
        /** @var list<TypeId> */
        private(set) array $typeIds,
        public readonly string $name,
        public readonly string $email,
        private(set) HashedPassword $password,
        public readonly ?int $userId,
        public readonly DateTimeImmutable $createdAt,
        private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * @param  list<TypeId>  $typeIds
     */
    public static function register(
        ActorId $uuid,
        ?AccountId $accountId,
        array $typeIds,
        string $name,
        string $email,
        HashedPassword $password,
        ?int $userId,
        DateTimeImmutable $createdAt,
        EmailUniquenessChecker $emailUniquenessChecker,
    ): self {
        if ($emailUniquenessChecker->isEmailTaken($email)) {
            throw ActorAlreadyExistsException::withEmail($email);
        }

        $actor = new self(
            uuid: $uuid,
            accountId: $accountId,
            typeIds: $typeIds,
            name: $name,
            email: $email,
            password: $password,
            userId: $userId,
            createdAt: $createdAt,
        );

        $actor->recordEvent(new ActorRegistered($uuid, $accountId, $email));

        return $actor;
    }

    public function hasTypeId(TypeId $typeId): bool
    {
        foreach ($this->typeIds as $id) {
            if ($id->equals($typeId)) {
                return true;
            }
        }

        return false;
    }

    public function assignType(TypeId $typeId): void
    {
        if (! $this->hasTypeId($typeId)) {
            $this->typeIds[] = $typeId;
            $this->updatedAt = new DateTimeImmutable;
        }
    }

    /** @return list<TypeId> */
    public function typeIds(): array
    {
        return $this->typeIds;
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
