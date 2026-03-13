<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory\Application;

use Modules\Inventory\Application\Query\GetRoomStats;
use Modules\Inventory\Application\Query\GetRoomStatsHandler;
use Modules\Inventory\Application\Query\RoomStatsResult;
use Modules\Inventory\Domain\Repository\RoomRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetRoomStatsHandler::class)]
final class GetRoomStatsHandlerTest extends TestCase
{
    #[Test]
    public function itReturnsRoomStatsDto(): void
    {
        $repository = $this->createMock(RoomRepository::class);

        $repository->method('count')->willReturn(10);
        $repository->method('countByStatus')->willReturn([
            'available' => 7,
            'occupied' => 2,
            'maintenance' => 1,
        ]);
        $repository->method('countByType')->willReturn([
            'SINGLE' => 3,
            'DOUBLE' => 5,
            'SUITE' => 2,
        ]);

        $handler = new GetRoomStatsHandler($repository);
        $result = $handler->handle(new GetRoomStats());

        $this->assertInstanceOf(RoomStatsResult::class, $result);
        $this->assertSame(10, $result->total);
        $this->assertSame(7, $result->byStatus['available']);
        $this->assertSame(5, $result->byType['DOUBLE']);
    }

    #[Test]
    public function toArrayReturnsCorrectStructure(): void
    {
        $repository = $this->createMock(RoomRepository::class);

        $repository->method('count')->willReturn(5);
        $repository->method('countByStatus')->willReturn(['available' => 5]);
        $repository->method('countByType')->willReturn(['SUITE' => 5]);

        $handler = new GetRoomStatsHandler($repository);
        $array = $handler->handle(new GetRoomStats())->toArray();

        $this->assertSame(5, $array['total']);
        $this->assertSame(['available' => 5], $array['by_status']);
        $this->assertSame(['SUITE' => 5], $array['by_type']);
    }
}
