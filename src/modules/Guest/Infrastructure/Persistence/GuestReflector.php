<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\Guest\Domain\Guest;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use ReflectionClass;

final class GuestReflector
{
    /**
     * @param  string[]  $preferences
     */
    public static function reconstruct(
        GuestId $uuid,
        string $fullName,
        string $email,
        string $phone,
        string $document,
        LoyaltyTier $loyaltyTier,
        array $preferences,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
    ): Guest {
        $ref = new ReflectionClass(Guest::class);
        $guest = $ref->newInstanceWithoutConstructor();

        self::set($ref, $guest, 'uuid', $uuid);
        self::set($ref, $guest, 'fullName', $fullName);
        self::set($ref, $guest, 'email', $email);
        self::set($ref, $guest, 'phone', $phone);
        self::set($ref, $guest, 'document', $document);
        self::set($ref, $guest, 'loyaltyTier', $loyaltyTier);
        self::set($ref, $guest, 'preferences', $preferences);
        self::set($ref, $guest, 'createdAt', $createdAt);
        self::set($ref, $guest, 'updatedAt', $updatedAt);

        return $guest;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
