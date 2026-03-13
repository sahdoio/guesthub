<?php

declare(strict_types=1);

namespace Tests\Integration\Guest;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GuestProfileStatsTest extends TestCase
{
    use RefreshDatabase;

    private GuestProfileRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(GuestProfileRepository::class);
    }

    private function createGuest(string $loyaltyTier = 'bronze'): void
    {
        $profile = GuestProfile::create(
            uuid: $this->repository->nextIdentity(),
            fullName: 'Guest ' . uniqid(),
            email: uniqid() . '@hotel.com',
            phone: '+5511999999999',
            document: 'DOC' . uniqid(),
            loyaltyTier: LoyaltyTier::from($loyaltyTier),
            preferences: [],
            createdAt: new DateTimeImmutable(),
        );

        $this->repository->save($profile);
    }

    #[Test]
    public function countReturnsZeroWithNoGuests(): void
    {
        $this->assertSame(0, $this->repository->count());
    }

    #[Test]
    public function countReturnsTotalGuests(): void
    {
        $this->createGuest();
        $this->createGuest();
        $this->createGuest();

        $this->assertSame(3, $this->repository->count());
    }

    #[Test]
    public function countByLoyaltyTierReturnsEmptyWhenNoGuests(): void
    {
        $this->assertSame([], $this->repository->countByLoyaltyTier());
    }

    #[Test]
    public function countByLoyaltyTierGroupsCorrectly(): void
    {
        $this->createGuest('bronze');
        $this->createGuest('bronze');
        $this->createGuest('gold');
        $this->createGuest('platinum');

        $result = $this->repository->countByLoyaltyTier();

        $this->assertSame(2, $result['bronze']);
        $this->assertSame(1, $result['gold']);
        $this->assertSame(1, $result['platinum']);
        $this->assertArrayNotHasKey('silver', $result);
    }
}
