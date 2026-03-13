<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Application;

use Modules\Reservation\Application\Query\GetReservationStats;
use Modules\Reservation\Application\Query\GetReservationStatsHandler;
use Modules\Reservation\Application\Query\ReservationStatsResult;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetReservationStatsHandler::class)]
final class GetReservationStatsHandlerTest extends TestCase
{
    #[Test]
    public function itReturnsReservationStatsDto(): void
    {
        $repository = $this->createMock(ReservationRepository::class);

        $repository->method('count')->willReturn(15);
        $repository->method('countByStatus')->willReturn([
            'pending' => 5,
            'confirmed' => 4,
            'checked_in' => 3,
            'checked_out' => 2,
            'cancelled' => 1,
        ]);
        $repository->method('countByRoomType')->willReturn([
            'SINGLE' => 5,
            'DOUBLE' => 7,
            'SUITE' => 3,
        ]);
        $repository->method('countTodayCheckIns')->willReturn(2);
        $repository->method('countTodayCheckOuts')->willReturn(1);

        $handler = new GetReservationStatsHandler($repository);
        $result = $handler->handle(new GetReservationStats());

        $this->assertInstanceOf(ReservationStatsResult::class, $result);
        $this->assertSame(15, $result->total);
        $this->assertSame(5, $result->byStatus['pending']);
        $this->assertSame(7, $result->byRoomType['DOUBLE']);
        $this->assertSame(2, $result->todayCheckIns);
        $this->assertSame(1, $result->todayCheckOuts);
    }

    #[Test]
    public function itReturnsEmptyStatsWhenNoReservations(): void
    {
        $repository = $this->createMock(ReservationRepository::class);

        $repository->method('count')->willReturn(0);
        $repository->method('countByStatus')->willReturn([]);
        $repository->method('countByRoomType')->willReturn([]);
        $repository->method('countTodayCheckIns')->willReturn(0);
        $repository->method('countTodayCheckOuts')->willReturn(0);

        $handler = new GetReservationStatsHandler($repository);
        $result = $handler->handle(new GetReservationStats());

        $this->assertSame(0, $result->total);
        $this->assertEmpty($result->byStatus);
        $this->assertEmpty($result->byRoomType);
        $this->assertSame(0, $result->todayCheckIns);
        $this->assertSame(0, $result->todayCheckOuts);
    }

    #[Test]
    public function toArrayReturnsCorrectStructure(): void
    {
        $repository = $this->createMock(ReservationRepository::class);

        $repository->method('count')->willReturn(3);
        $repository->method('countByStatus')->willReturn(['pending' => 3]);
        $repository->method('countByRoomType')->willReturn(['SUITE' => 3]);
        $repository->method('countTodayCheckIns')->willReturn(1);
        $repository->method('countTodayCheckOuts')->willReturn(0);

        $handler = new GetReservationStatsHandler($repository);
        $array = $handler->handle(new GetReservationStats())->toArray();

        $this->assertSame(3, $array['total']);
        $this->assertSame(['pending' => 3], $array['by_status']);
        $this->assertSame(['SUITE' => 3], $array['by_room_type']);
        $this->assertSame(1, $array['today_check_ins']);
        $this->assertSame(0, $array['today_check_outs']);
    }
}
