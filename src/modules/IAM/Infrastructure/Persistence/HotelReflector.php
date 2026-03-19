<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Hotel;
use Modules\IAM\Domain\HotelId;
use ReflectionClass;

final class HotelReflector
{
    public static function reconstruct(
        HotelId $uuid,
        AccountId $accountId,
        string $name,
        string $slug,
        ?string $description,
        ?string $address,
        ?string $contactEmail,
        ?string $contactPhone,
        string $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
    ): Hotel {
        $ref = new ReflectionClass(Hotel::class);
        $hotel = $ref->newInstanceWithoutConstructor();

        self::set($ref, $hotel, 'uuid', $uuid);
        self::set($ref, $hotel, 'accountId', $accountId);
        self::set($ref, $hotel, 'name', $name);
        self::set($ref, $hotel, 'slug', $slug);
        self::set($ref, $hotel, 'description', $description);
        self::set($ref, $hotel, 'address', $address);
        self::set($ref, $hotel, 'contactEmail', $contactEmail);
        self::set($ref, $hotel, 'contactPhone', $contactPhone);
        self::set($ref, $hotel, 'status', $status);
        self::set($ref, $hotel, 'createdAt', $createdAt);
        self::set($ref, $hotel, 'updatedAt', $updatedAt);

        return $hotel;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
