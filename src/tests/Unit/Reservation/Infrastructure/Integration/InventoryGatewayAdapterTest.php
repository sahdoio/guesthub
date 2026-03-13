<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Infrastructure\Integration;

use DateTimeImmutable;
use Modules\Inventory\Infrastructure\Integration\Dto\RoomData;
use Modules\Inventory\Infrastructure\Integration\InventoryApi;
use Modules\Reservation\Domain\Dto\AvailableRoom;
use Modules\Reservation\Domain\Dto\RoomAvailability;
use Modules\Reservation\Domain\Dto\RoomTypeInfo;
use Modules\Reservation\Infrastructure\Integration\InventoryGatewayAdapter;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InventoryGatewayAdapter::class)]
final class InventoryGatewayAdapterTest extends TestCase
{
    private InventoryGatewayAdapter $adapter;
    private InventoryApi $inventoryApi;

    protected function setUp(): void
    {
        $this->inventoryApi = $this->createMock(InventoryApi::class);
        $this->adapter = new InventoryGatewayAdapter($this->inventoryApi);
    }

    #[Test]
    public function itReturnsAvailabilityForSingleRoom(): void
    {
        $this->inventoryApi->method('countAvailableByType')
            ->with('SINGLE')
            ->willReturn(10);

        $period = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+3 days'),
        );

        $result = $this->adapter->checkAvailability('SINGLE', $period);

        $this->assertInstanceOf(RoomAvailability::class, $result);
        $this->assertSame('SINGLE', $result->roomType);
        $this->assertSame(10, $result->availableCount);
        $this->assertSame(150.00, $result->pricePerNight);
    }

    #[Test]
    public function itReturnsAvailabilityForSuite(): void
    {
        $this->inventoryApi->method('countAvailableByType')
            ->with('SUITE')
            ->willReturn(3);

        $period = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+3 days'),
        );

        $result = $this->adapter->checkAvailability('SUITE', $period);

        $this->assertSame(500.00, $result->pricePerNight);
    }

    #[Test]
    public function itReturnsRoomTypeInfo(): void
    {
        $result = $this->adapter->getRoomTypeInfo('DOUBLE');

        $this->assertInstanceOf(RoomTypeInfo::class, $result);
        $this->assertSame('DOUBLE', $result->type);
        $this->assertSame(2, $result->capacity);
        $this->assertContains('wifi', $result->amenities);
    }

    #[Test]
    public function itReturnsDefaultForUnknownRoomType(): void
    {
        $this->inventoryApi->method('countAvailableByType')
            ->with('UNKNOWN')
            ->willReturn(0);

        $period = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+3 days'),
        );

        $result = $this->adapter->checkAvailability('UNKNOWN', $period);

        $this->assertSame(200.00, $result->pricePerNight);
    }

    #[Test]
    public function itListsAvailableRooms(): void
    {
        $this->inventoryApi->method('listAvailableByType')
            ->with('DOUBLE')
            ->willReturn([
                new RoomData('uuid-1', '201', 'DOUBLE', 2, 2, 250.00, 'available', ['wifi']),
                new RoomData('uuid-2', '202', 'DOUBLE', 2, 2, 250.00, 'available', ['wifi']),
            ]);

        $result = $this->adapter->listAvailableRooms('DOUBLE');

        $this->assertCount(2, $result);
        $this->assertInstanceOf(AvailableRoom::class, $result[0]);
        $this->assertSame('201', $result[0]->number);
        $this->assertSame(2, $result[0]->floor);
    }

    #[Test]
    public function itChecksRoomAvailability(): void
    {
        $this->inventoryApi->method('isRoomAvailable')
            ->with('201')
            ->willReturn(true);

        $this->assertTrue($this->adapter->isRoomAvailable('201'));
    }

    #[Test]
    public function itReturnsFalseForUnavailableRoom(): void
    {
        $this->inventoryApi->method('isRoomAvailable')
            ->with('999')
            ->willReturn(false);

        $this->assertFalse($this->adapter->isRoomAvailable('999'));
    }
}
