<?php

declare(strict_types=1);

namespace Tests\Integration\Guest;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Guest\Infrastructure\Persistence\Eloquent\EloquentGuestProfileRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(EloquentGuestProfileRepository::class)]
final class EloquentGuestProfileRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private GuestProfileRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(GuestProfileRepository::class);
    }

    private function createProfile(array $overrides = []): GuestProfile
    {
        return GuestProfile::create(
            uuid: $overrides['uuid'] ?? $this->repository->nextIdentity(),
            fullName: $overrides['fullName'] ?? 'Alice Johnson',
            email: $overrides['email'] ?? 'alice@hotel.com',
            phone: $overrides['phone'] ?? '+5511999999999',
            document: $overrides['document'] ?? 'ABC123',
            loyaltyTier: $overrides['loyaltyTier'] ?? LoyaltyTier::BRONZE,
            preferences: $overrides['preferences'] ?? [],
            createdAt: $overrides['createdAt'] ?? new DateTimeImmutable(),
        );
    }

    #[Test]
    public function itSavesAndFindsByUuid(): void
    {
        $profile = $this->createProfile();
        $this->repository->save($profile);

        $found = $this->repository->findByUuid($profile->uuid);

        $this->assertNotNull($found);
        $this->assertTrue($profile->uuid->equals($found->uuid));
        $this->assertSame('Alice Johnson', $found->fullName);
        $this->assertSame('alice@hotel.com', $found->email);
    }

    #[Test]
    public function itReturnsNullForUnknownUuid(): void
    {
        $this->assertNull($this->repository->findByUuid(GuestProfileId::generate()));
    }

    #[Test]
    public function itFindsByEmail(): void
    {
        $profile = $this->createProfile(['email' => 'bob@hotel.com']);
        $this->repository->save($profile);

        $found = $this->repository->findByEmail('bob@hotel.com');

        $this->assertNotNull($found);
        $this->assertSame('bob@hotel.com', $found->email);
    }

    #[Test]
    public function itFindsByDocument(): void
    {
        $profile = $this->createProfile(['document' => 'XYZ999']);
        $this->repository->save($profile);

        $found = $this->repository->findByDocument('XYZ999');

        $this->assertNotNull($found);
        $this->assertSame('XYZ999', $found->document);
    }

    #[Test]
    public function itUpdatesExistingProfile(): void
    {
        $profile = $this->createProfile();
        $this->repository->save($profile);

        $profile->updateContactInfo('Alice Updated', 'alice.new@hotel.com', '+5511000000000');
        $this->repository->save($profile);

        $found = $this->repository->findByUuid($profile->uuid);

        $this->assertSame('Alice Updated', $found->fullName);
        $this->assertSame('alice.new@hotel.com', $found->email);
    }

    #[Test]
    public function itRemovesAProfile(): void
    {
        $profile = $this->createProfile();
        $this->repository->save($profile);
        $this->assertNotNull($this->repository->findByUuid($profile->uuid));

        $this->repository->remove($profile);

        $this->assertNull($this->repository->findByUuid($profile->uuid));
    }

    #[Test]
    public function itPaginatesProfiles(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->repository->save($this->createProfile([
                'email' => "guest{$i}@hotel.com",
                'document' => "DOC{$i}",
            ]));
        }

        $result = $this->repository->paginate(1, 2);

        $this->assertSame(3, $result->total);
        $this->assertSame(2, $result->perPage);
        $this->assertCount(2, $result->items);
        $this->assertSame(2, $result->lastPage);
    }

    #[Test]
    public function itGeneratesUniqueIdentities(): void
    {
        $id1 = $this->repository->nextIdentity();
        $id2 = $this->repository->nextIdentity();

        $this->assertFalse($id1->equals($id2));
    }
}
