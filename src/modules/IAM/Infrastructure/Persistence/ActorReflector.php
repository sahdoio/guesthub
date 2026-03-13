<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Role;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use ReflectionClass;

final class ActorReflector
{
    /**
     * @param  list<Role>  $roles
     */
    public static function reconstruct(
        ActorId $uuid,
        ?AccountId $accountId,
        array $roles,
        string $name,
        string $email,
        HashedPassword $password,
        ?string $subjectType,
        ?int $subjectId,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
    ): Actor {
        $ref = new ReflectionClass(Actor::class);
        $actor = $ref->newInstanceWithoutConstructor();

        self::set($ref, $actor, 'uuid', $uuid);
        self::set($ref, $actor, 'accountId', $accountId);
        self::set($ref, $actor, 'roles', $roles);
        self::set($ref, $actor, 'name', $name);
        self::set($ref, $actor, 'email', $email);
        self::set($ref, $actor, 'password', $password);
        self::set($ref, $actor, 'subjectType', $subjectType);
        self::set($ref, $actor, 'subjectId', $subjectId);
        self::set($ref, $actor, 'createdAt', $createdAt);
        self::set($ref, $actor, 'updatedAt', $updatedAt);

        return $actor;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
