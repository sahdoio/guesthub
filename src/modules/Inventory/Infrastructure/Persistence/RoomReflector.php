<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\Inventory\Domain\Room;
use Modules\Inventory\Domain\RoomId;
use Modules\Inventory\Domain\ValueObject\RoomStatus;
use Modules\Inventory\Domain\ValueObject\RoomType;
use ReflectionClass;

final class RoomReflector
{
    /**
     * @param  string[]  $amenities
     */
    public static function reconstruct(
        RoomId $uuid,
        string $number,
        RoomType $type,
        int $floor,
        int $capacity,
        float $pricePerNight,
        RoomStatus $status,
        array $amenities,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
    ): Room {
        $ref = new ReflectionClass(Room::class);
        $room = $ref->newInstanceWithoutConstructor();

        self::set($ref, $room, 'uuid', $uuid);
        self::set($ref, $room, 'number', $number);
        self::set($ref, $room, 'type', $type);
        self::set($ref, $room, 'floor', $floor);
        self::set($ref, $room, 'capacity', $capacity);
        self::set($ref, $room, 'pricePerNight', $pricePerNight);
        self::set($ref, $room, 'status', $status);
        self::set($ref, $room, 'amenities', $amenities);
        self::set($ref, $room, 'createdAt', $createdAt);
        self::set($ref, $room, 'updatedAt', $updatedAt);

        return $room;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
