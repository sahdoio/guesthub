<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\IAM\Domain\Account;
use Modules\IAM\Domain\AccountId;
use ReflectionClass;

final class AccountReflector
{
    public static function reconstruct(
        AccountId $uuid,
        string $name,
        string $slug,
        string $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
    ): Account {
        $ref = new ReflectionClass(Account::class);
        $account = $ref->newInstanceWithoutConstructor();

        self::set($ref, $account, 'uuid', $uuid);
        self::set($ref, $account, 'name', $name);
        self::set($ref, $account, 'slug', $slug);
        self::set($ref, $account, 'status', $status);
        self::set($ref, $account, 'createdAt', $createdAt);
        self::set($ref, $account, 'updatedAt', $updatedAt);

        return $account;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
