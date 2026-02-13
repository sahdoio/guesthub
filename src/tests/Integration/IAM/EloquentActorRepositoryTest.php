<?php

declare(strict_types=1);

namespace Tests\Integration\IAM;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\ValueObject\ActorType;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentActorRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(EloquentActorRepository::class)]
final class EloquentActorRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ActorRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(ActorRepository::class);
    }

    private function registerActor(array $overrides = []): Actor
    {
        return Actor::register(
            uuid: $overrides['uuid'] ?? $this->repository->nextIdentity(),
            type: $overrides['type'] ?? ActorType::GUEST,
            name: $overrides['name'] ?? 'John Doe',
            email: $overrides['email'] ?? 'john@hotel.com',
            password: $overrides['password'] ?? new HashedPassword('$2y$10$somehash'),
            profileType: array_key_exists('profileType', $overrides) ? $overrides['profileType'] : 'guest',
            profileId: array_key_exists('profileId', $overrides) ? $overrides['profileId'] : 'profile-uuid-123',
            createdAt: $overrides['createdAt'] ?? new DateTimeImmutable(),
        );
    }

    #[Test]
    public function itSavesAndFindsByUuid(): void
    {
        $actor = $this->registerActor();
        $this->repository->save($actor);

        $found = $this->repository->findByUuid($actor->uuid);

        $this->assertNotNull($found);
        $this->assertTrue($actor->uuid->equals($found->uuid));
        $this->assertSame('John Doe', $found->name);
        $this->assertSame('john@hotel.com', $found->email);
        $this->assertSame(ActorType::GUEST, $found->type);
        $this->assertSame('guest', $found->profileType);
        $this->assertSame('profile-uuid-123', $found->profileId);
    }

    #[Test]
    public function itReturnsNullForUnknownUuid(): void
    {
        $this->assertNull($this->repository->findByUuid(ActorId::generate()));
    }

    #[Test]
    public function itFindsByEmail(): void
    {
        $actor = $this->registerActor(['email' => 'jane@hotel.com']);
        $this->repository->save($actor);

        $found = $this->repository->findByEmail('jane@hotel.com');

        $this->assertNotNull($found);
        $this->assertSame('jane@hotel.com', $found->email);
    }

    #[Test]
    public function itReturnsNullForUnknownEmail(): void
    {
        $this->assertNull($this->repository->findByEmail('nonexistent@hotel.com'));
    }

    #[Test]
    public function itUpdatesExistingActor(): void
    {
        $actor = $this->registerActor();
        $this->repository->save($actor);

        $actor->changePassword(new HashedPassword('$2y$10$newhash'));
        $this->repository->save($actor);

        $found = $this->repository->findByUuid($actor->uuid);

        $this->assertSame('$2y$10$newhash', $found->password->value);
    }

    #[Test]
    public function itSavesSystemActorWithNullProfile(): void
    {
        $actor = $this->registerActor([
            'type' => ActorType::SYSTEM,
            'profileType' => null,
            'profileId' => null,
        ]);
        $this->repository->save($actor);

        $found = $this->repository->findByUuid($actor->uuid);

        $this->assertSame(ActorType::SYSTEM, $found->type);
        $this->assertNull($found->profileType);
        $this->assertNull($found->profileId);
    }

    #[Test]
    public function itGeneratesUniqueIdentities(): void
    {
        $id1 = $this->repository->nextIdentity();
        $id2 = $this->repository->nextIdentity();

        $this->assertFalse($id1->equals($id2));
    }
}
