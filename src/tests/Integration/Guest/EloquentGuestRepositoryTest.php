<?php

declare(strict_types=1);

namespace Tests\Integration\Guest;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\UserId;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

#[CoversClass(EloquentUserRepository::class)]
final class EloquentGuestRepositoryTest extends TestCase
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
        $this->app->make(TenantContext::class)->set($account->id);

        $this->repository = $this->app->make(UserRepository::class);
    }

    private function createProfile(array $overrides = []): User
    {
        return User::create(
            uuid: $overrides['uuid'] ?? $this->repository->nextIdentity(),
            fullName: $overrides['fullName'] ?? 'Alice Johnson',
            email: $overrides['email'] ?? 'alice@hotel.com',
            phone: $overrides['phone'] ?? '5511999999999',
            document: $overrides['document'] ?? 'ABC123',
            loyaltyTier: $overrides['loyaltyTier'] ?? LoyaltyTier::BRONZE,
            preferences: $overrides['preferences'] ?? [],
            createdAt: $overrides['createdAt'] ?? new DateTimeImmutable,
        );
    }

    #[Test]
    public function it_saves_and_finds_by_uuid(): void
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
    public function it_returns_null_for_unknown_uuid(): void
    {
        $this->assertNull($this->repository->findByUuid(UserId::generate()));
    }

    #[Test]
    public function it_finds_by_email(): void
    {
        $profile = $this->createProfile(['email' => 'bob@hotel.com']);
        $this->repository->save($profile);

        $found = $this->repository->findByEmail('bob@hotel.com');

        $this->assertNotNull($found);
        $this->assertSame('bob@hotel.com', $found->email);
    }

    #[Test]
    public function it_finds_by_document(): void
    {
        $profile = $this->createProfile(['document' => 'XYZ999']);
        $this->repository->save($profile);

        $found = $this->repository->findByDocument('XYZ999');

        $this->assertNotNull($found);
        $this->assertSame('XYZ999', $found->document);
    }

    #[Test]
    public function it_updates_existing_profile(): void
    {
        $profile = $this->createProfile();
        $this->repository->save($profile);

        $profile->updateContactInfo('Alice Updated', 'alice.new@hotel.com', '5511000000000');
        $this->repository->save($profile);

        $found = $this->repository->findByUuid($profile->uuid);

        $this->assertSame('Alice Updated', $found->fullName);
        $this->assertSame('alice.new@hotel.com', $found->email);
    }

    #[Test]
    public function it_removes_a_profile(): void
    {
        $profile = $this->createProfile();
        $this->repository->save($profile);
        $this->assertNotNull($this->repository->findByUuid($profile->uuid));

        $this->repository->remove($profile);

        $this->assertNull($this->repository->findByUuid($profile->uuid));
    }

    #[Test]
    public function it_lists_profiles(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->repository->save($this->createProfile([
                'email' => "guest{$i}@hotel.com",
                'document' => "DOC{$i}",
            ]));
        }

        $result = $this->repository->list(1, 2);

        $this->assertSame(3, $result->total);
        $this->assertSame(2, $result->perPage);
        $this->assertCount(2, $result->items);
        $this->assertSame(2, $result->lastPage);
    }

    #[Test]
    public function it_generates_unique_identities(): void
    {
        $id1 = $this->repository->nextIdentity();
        $id2 = $this->repository->nextIdentity();

        $this->assertFalse($id1->equals($id2));
    }
}
