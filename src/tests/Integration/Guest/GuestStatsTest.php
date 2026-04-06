<?php

declare(strict_types=1);

namespace Tests\Integration\Guest;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class GuestStatsTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $account = AccountModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'Test Hotel',
            'slug' => 'test-hotel',
            'status' => 'active',
            'created_at' => now(),
        ]);
        $this->app->make(TenantContext::class)->set($account->uuid);

        $this->repository = $this->app->make(UserRepository::class);
    }

    private function createGuest(string $loyaltyTier = 'bronze'): void
    {
        $profile = User::create(
            uuid: $this->repository->nextIdentity(),
            fullName: 'Guest '.uniqid(),
            email: uniqid().'@hotel.com',
            phone: '5511999999999',
            document: 'DOC'.uniqid(),
            loyaltyTier: LoyaltyTier::from($loyaltyTier),
            preferences: [],
            createdAt: new DateTimeImmutable,
            hashedPassword: 'hashed_default',
            actorType: 'guest',
            emailUniquenessChecker: $this->app->make(\Modules\IAM\Domain\Service\UserEmailUniquenessChecker::class),
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
