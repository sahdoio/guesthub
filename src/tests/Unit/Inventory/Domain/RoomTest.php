<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory\Domain;

use DateTimeImmutable;
use Modules\Inventory\Domain\Exception\InvalidRoomStateException;
use Modules\Inventory\Domain\Room;
use Modules\Inventory\Domain\RoomId;
use Modules\Inventory\Domain\ValueObject\RoomStatus;
use Modules\Inventory\Domain\ValueObject\RoomType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Room::class)]
final class RoomTest extends TestCase
{
    private function createRoom(): Room
    {
        return Room::create(
            uuid: RoomId::generate(),
            number: '201',
            type: RoomType::DOUBLE,
            floor: 2,
            capacity: 2,
            pricePerNight: 250.00,
            amenities: ['wifi', 'tv'],
            createdAt: new DateTimeImmutable(),
        );
    }

    #[Test]
    public function itCreatesARoomWithAvailableStatus(): void
    {
        $room = $this->createRoom();

        $this->assertSame('201', $room->number);
        $this->assertSame(RoomType::DOUBLE, $room->type);
        $this->assertSame(2, $room->floor);
        $this->assertSame(2, $room->capacity);
        $this->assertSame(250.00, $room->pricePerNight);
        $this->assertSame(RoomStatus::AVAILABLE, $room->status);
        $this->assertSame(['wifi', 'tv'], $room->amenities);
        $this->assertNull($room->updatedAt);
    }

    #[Test]
    public function itCanBeOccupied(): void
    {
        $room = $this->createRoom();
        $room->occupy();

        $this->assertSame(RoomStatus::OCCUPIED, $room->status);
        $this->assertNotNull($room->updatedAt);
    }

    #[Test]
    public function itCanBeReleasedFromOccupied(): void
    {
        $room = $this->createRoom();
        $room->occupy();
        $room->release();

        $this->assertSame(RoomStatus::AVAILABLE, $room->status);
    }

    #[Test]
    public function itCannotBeOccupiedWhenNotAvailable(): void
    {
        $room = $this->createRoom();
        $room->markMaintenance();

        $this->expectException(InvalidRoomStateException::class);
        $room->occupy();
    }

    #[Test]
    public function itCannotBeReleasedWhenNotOccupied(): void
    {
        $room = $this->createRoom();

        $this->expectException(InvalidRoomStateException::class);
        $room->release();
    }

    #[Test]
    public function itCanBeMarkedForMaintenance(): void
    {
        $room = $this->createRoom();
        $room->markMaintenance();

        $this->assertSame(RoomStatus::MAINTENANCE, $room->status);
    }

    #[Test]
    public function itCannotBeMarkedForMaintenanceWhenOccupied(): void
    {
        $room = $this->createRoom();
        $room->occupy();

        $this->expectException(InvalidRoomStateException::class);
        $room->markMaintenance();
    }

    #[Test]
    public function itCanBeMarkedOutOfOrder(): void
    {
        $room = $this->createRoom();
        $room->markOutOfOrder();

        $this->assertSame(RoomStatus::OUT_OF_ORDER, $room->status);
    }

    #[Test]
    public function itCannotBeMarkedOutOfOrderWhenOccupied(): void
    {
        $room = $this->createRoom();
        $room->occupy();

        $this->expectException(InvalidRoomStateException::class);
        $room->markOutOfOrder();
    }

    #[Test]
    public function itCanBeMarkedAvailableFromMaintenance(): void
    {
        $room = $this->createRoom();
        $room->markMaintenance();
        $room->markAvailable();

        $this->assertSame(RoomStatus::AVAILABLE, $room->status);
    }

    #[Test]
    public function itCannotBeMarkedAvailableWhenOccupied(): void
    {
        $room = $this->createRoom();
        $room->occupy();

        $this->expectException(InvalidRoomStateException::class);
        $room->markAvailable();
    }

    #[Test]
    public function itUpdatesPrice(): void
    {
        $room = $this->createRoom();
        $room->updateDetails(pricePerNight: 300.00);

        $this->assertSame(300.00, $room->pricePerNight);
        $this->assertNotNull($room->updatedAt);
    }

    #[Test]
    public function itUpdatesAmenities(): void
    {
        $room = $this->createRoom();
        $room->updateDetails(amenities: ['wifi', 'tv', 'minibar']);

        $this->assertSame(['wifi', 'tv', 'minibar'], $room->amenities);
    }

    #[Test]
    public function itUpdatesOnlyProvidedFields(): void
    {
        $room = $this->createRoom();
        $room->updateDetails(pricePerNight: 999.00);

        $this->assertSame(999.00, $room->pricePerNight);
        $this->assertSame(['wifi', 'tv'], $room->amenities);
    }
}
