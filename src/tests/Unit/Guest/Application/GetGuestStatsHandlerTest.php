<?php

declare(strict_types=1);

namespace Tests\Unit\Guest\Application;

use Modules\User\Application\Query\GetUserStats;
use Modules\User\Application\Query\GetUserStatsHandler;
use Modules\User\Application\Query\UserStatsResult;
use Modules\User\Domain\Repository\UserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetUserStatsHandler::class)]
final class GetGuestStatsHandlerTest extends TestCase
{
    #[Test]
    public function it_returns_guest_stats_dto(): void
    {
        $repository = $this->createMock(UserRepository::class);

        $repository->method('count')->willReturn(42);
        $repository->method('countByLoyaltyTier')->willReturn([
            'bronze' => 20,
            'silver' => 12,
            'gold' => 7,
            'platinum' => 3,
        ]);

        $handler = new GetUserStatsHandler($repository);
        $result = $handler->handle(new GetUserStats);

        $this->assertInstanceOf(UserStatsResult::class, $result);
        $this->assertSame(42, $result->total);
        $this->assertSame(20, $result->byLoyaltyTier['bronze']);
        $this->assertSame(12, $result->byLoyaltyTier['silver']);
        $this->assertSame(7, $result->byLoyaltyTier['gold']);
        $this->assertSame(3, $result->byLoyaltyTier['platinum']);
    }

    #[Test]
    public function it_returns_empty_stats_when_no_guests(): void
    {
        $repository = $this->createMock(UserRepository::class);

        $repository->method('count')->willReturn(0);
        $repository->method('countByLoyaltyTier')->willReturn([]);

        $handler = new GetUserStatsHandler($repository);
        $result = $handler->handle(new GetUserStats);

        $this->assertSame(0, $result->total);
        $this->assertEmpty($result->byLoyaltyTier);
    }

    #[Test]
    public function to_array_returns_correct_structure(): void
    {
        $repository = $this->createMock(UserRepository::class);

        $repository->method('count')->willReturn(5);
        $repository->method('countByLoyaltyTier')->willReturn(['gold' => 5]);

        $handler = new GetUserStatsHandler($repository);
        $result = $handler->handle(new GetUserStats);

        $array = $result->toArray();

        $this->assertSame(5, $array['total']);
        $this->assertSame(['gold' => 5], $array['by_loyalty_tier']);
    }
}
