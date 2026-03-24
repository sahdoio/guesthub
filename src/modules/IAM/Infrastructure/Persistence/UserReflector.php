<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\UserId;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use ReflectionClass;

final class UserReflector
{
    /**
     * @param  string[]  $preferences
     */
    public static function reconstruct(
        UserId $uuid,
        string $fullName,
        string $email,
        string $phone,
        string $document,
        ?LoyaltyTier $loyaltyTier,
        array $preferences,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
    ): User {
        $ref = new ReflectionClass(User::class);
        $user = $ref->newInstanceWithoutConstructor();

        self::set($ref, $user, 'uuid', $uuid);
        self::set($ref, $user, 'fullName', $fullName);
        self::set($ref, $user, 'email', $email);
        self::set($ref, $user, 'phone', $phone);
        self::set($ref, $user, 'document', $document);
        self::set($ref, $user, 'loyaltyTier', $loyaltyTier);
        self::set($ref, $user, 'preferences', $preferences);
        self::set($ref, $user, 'createdAt', $createdAt);
        self::set($ref, $user, 'updatedAt', $updatedAt);

        return $user;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
