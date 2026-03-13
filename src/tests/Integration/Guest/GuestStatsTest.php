<?php

declare(strict_types=1);

namespace Tests\Integration\Guest;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\Guest;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class GuestStatsTest extends TestCase
{
    use RefreshDatabase;

    private GuestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $account = AccountModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'Test Hotel',
            'created_at' => now(),
        ]);
        $this->app->make(TenantContext::class)->set($account->id);

        $this->repository = $this->app->make(GuestRepository::class);
    }

    private function createGuest(string $loyaltyTier = 'bronze'): void
    {
        $profile = Guest::create(
            uuid: $this->repository->nextIdentity(),
            fullName: 'Guest '.uniqid(),
            email: uniqid().'@hotel.com',
            phone: '+5511999999999',
            document: 'DOC'.uniqid(),
            loyaltyTier: LoyaltyTier::from($loyaltyTier),
            preferences: [],
            createdAt: new DateTimeImmutable,
        );

        $this->repository->save($profile);
    }

    #[Test]
    public function count_returns_zero_with_no_guests(): void
    {
        $this->assertSame(0, $this->repository->count());
    }

    #[Test]
    public function count_returns_total_guests(): void
    {
        $this->createGuest();
        $this->createGuest();
        $this->createGuest();

        $this->assertSame(3, $this->repository->count());
    }

    #[Test]
    public function count_by_loyalty_tier_returns_empty_when_no_guests(): void
    {
        $this->assertSame([], $this->repository->countByLoyaltyTier());
    }

    #[Test]
    public function count_by_loyalty_tier_groups_correctly(): void
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
