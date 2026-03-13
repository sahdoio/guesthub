<?php

declare(strict_types=1);

namespace Tests\Unit\Guest\Application;

use Modules\Guest\Application\Query\GetGuestStats;
use Modules\Guest\Application\Query\GetGuestStatsHandler;
use Modules\Guest\Application\Query\GuestStatsResult;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetGuestStatsHandler::class)]
final class GetGuestStatsHandlerTest extends TestCase
{
    #[Test]
    public function itReturnsGuestStatsDto(): void
    {
        $repository = $this->createMock(GuestProfileRepository::class);

        $repository->method('count')->willReturn(42);
        $repository->method('countByLoyaltyTier')->willReturn([
            'bronze' => 20,
            'silver' => 12,
            'gold' => 7,
            'platinum' => 3,
        ]);

        $handler = new GetGuestStatsHandler($repository);
        $result = $handler->handle(new GetGuestStats());

        $this->assertInstanceOf(GuestStatsResult::class, $result);
        $this->assertSame(42, $result->total);
        $this->assertSame(20, $result->byLoyaltyTier['bronze']);
        $this->assertSame(12, $result->byLoyaltyTier['silver']);
        $this->assertSame(7, $result->byLoyaltyTier['gold']);
        $this->assertSame(3, $result->byLoyaltyTier['platinum']);
    }

    #[Test]
    public function itReturnsEmptyStatsWhenNoGuests(): void
    {
        $repository = $this->createMock(GuestProfileRepository::class);

        $repository->method('count')->willReturn(0);
        $repository->method('countByLoyaltyTier')->willReturn([]);

        $handler = new GetGuestStatsHandler($repository);
        $result = $handler->handle(new GetGuestStats());

        $this->assertSame(0, $result->total);
        $this->assertEmpty($result->byLoyaltyTier);
    }

    #[Test]
    public function toArrayReturnsCorrectStructure(): void
    {
        $repository = $this->createMock(GuestProfileRepository::class);

        $repository->method('count')->willReturn(5);
        $repository->method('countByLoyaltyTier')->willReturn(['gold' => 5]);

        $handler = new GetGuestStatsHandler($repository);
        $result = $handler->handle(new GetGuestStats());

        $array = $result->toArray();

        $this->assertSame(5, $array['total']);
        $this->assertSame(['gold' => 5], $array['by_loyalty_tier']);
    }
}
