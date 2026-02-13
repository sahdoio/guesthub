<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Infrastructure\Integration;

use DateTimeImmutable;
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

    protected function setUp(): void
    {
        $this->adapter = new InventoryGatewayAdapter();
    }

    #[Test]
    public function itReturnsAvailabilityForSingleRoom(): void
    {
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
        $period = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+3 days'),
        );

        $result = $this->adapter->checkAvailability('UNKNOWN', $period);

        $this->assertSame(200.00, $result->pricePerNight);
    }
}
