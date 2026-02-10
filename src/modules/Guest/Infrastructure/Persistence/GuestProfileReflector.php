<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use ReflectionClass;

final class GuestProfileReflector
{
    /**
     * @param string[] $preferences
     */
    public static function reconstruct(
        GuestProfileId $uuid,
        string $fullName,
        string $email,
        string $phone,
        string $document,
        LoyaltyTier $loyaltyTier,
        array $preferences,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
    ): GuestProfile {
        $ref = new ReflectionClass(GuestProfile::class);
        $profile = $ref->newInstanceWithoutConstructor();

        self::set($ref, $profile, 'uuid', $uuid);
        self::set($ref, $profile, 'fullName', $fullName);
        self::set($ref, $profile, 'email', $email);
        self::set($ref, $profile, 'phone', $phone);
        self::set($ref, $profile, 'document', $document);
        self::set($ref, $profile, 'loyaltyTier', $loyaltyTier);
        self::set($ref, $profile, 'preferences', $preferences);
        self::set($ref, $profile, 'createdAt', $createdAt);
        self::set($ref, $profile, 'updatedAt', $updatedAt);

        return $profile;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
