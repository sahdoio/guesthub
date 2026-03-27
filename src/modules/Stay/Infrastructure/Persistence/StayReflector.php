<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\IAM\Domain\ValueObject\AccountId;
use Modules\Stay\Domain\Stay;
use Modules\Stay\Domain\StayId;
use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;
use ReflectionClass;

final class StayReflector
{
    public static function reconstruct(
        StayId $uuid,
        AccountId $accountId,
        string $name,
        string $slug,
        ?string $description,
        ?string $address,
        StayType $type,
        StayCategory $category,
        float $pricePerNight,
        int $capacity,
        ?string $contactEmail,
        ?string $contactPhone,
        string $status,
        ?array $amenities,
        ?string $coverImagePath,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
    ): Stay {
        $ref = new ReflectionClass(Stay::class);
        $stay = $ref->newInstanceWithoutConstructor();

        self::set($ref, $stay, 'uuid', $uuid);
        self::set($ref, $stay, 'accountId', $accountId);
        self::set($ref, $stay, 'name', $name);
        self::set($ref, $stay, 'slug', $slug);
        self::set($ref, $stay, 'description', $description);
        self::set($ref, $stay, 'address', $address);
        self::set($ref, $stay, 'type', $type);
        self::set($ref, $stay, 'category', $category);
        self::set($ref, $stay, 'pricePerNight', $pricePerNight);
        self::set($ref, $stay, 'capacity', $capacity);
        self::set($ref, $stay, 'contactEmail', $contactEmail);
        self::set($ref, $stay, 'contactPhone', $contactPhone);
        self::set($ref, $stay, 'status', $status);
        self::set($ref, $stay, 'amenities', $amenities);
        self::set($ref, $stay, 'coverImagePath', $coverImagePath);
        self::set($ref, $stay, 'createdAt', $createdAt);
        self::set($ref, $stay, 'updatedAt', $updatedAt);

        return $stay;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
