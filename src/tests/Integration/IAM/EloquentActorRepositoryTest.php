<?php

declare(strict_types=1);

namespace Tests\Integration\IAM;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\Guest;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Guest\Infrastructure\Persistence\Eloquent\GuestModel;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Repository\RoleRepository;
use Modules\IAM\Domain\Account;
use Modules\IAM\Domain\Role;
use Modules\IAM\Domain\RoleId;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\IAM\Domain\ValueObject\RoleName;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentActorRepository;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(EloquentActorRepository::class)]
final class EloquentActorRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ActorRepository $repository;
    private AccountId $accountId;
    private Role $guestRole;
    private Role $adminRole;
    private int $guestId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(ActorRepository::class);

        // Seed roles
        $roleRepository = $this->app->make(RoleRepository::class);
        $this->guestRole = Role::create(uuid: $roleRepository->nextIdentity(), name: RoleName::GUEST);
        $roleRepository->save($this->guestRole);

        $this->adminRole = Role::create(uuid: $roleRepository->nextIdentity(), name: RoleName::ADMIN);
        $roleRepository->save($this->adminRole);

        // Seed account
        $accountRepository = $this->app->make(AccountRepository::class);
        $account = Account::create(
            uuid: $accountRepository->nextIdentity(),
            name: 'Test Hotel',
            createdAt: new DateTimeImmutable(),
        );
        $accountRepository->save($account);
        $this->accountId = $account->uuid;

        // Set tenant context
        $numericAccountId = (int) AccountModel::where('uuid', $account->uuid->value)->value('id');
        $this->app->make(TenantContext::class)->set($numericAccountId);

        // Seed a guest for FK reference
        $guestRepo = $this->app->make(GuestRepository::class);
        $guest = Guest::create(
            uuid: $guestRepo->nextIdentity(),
            fullName: 'John Doe',
            email: 'john@hotel.com',
            phone: '+5511999999999',
            document: 'DOC123',
            loyaltyTier: LoyaltyTier::BRONZE,
            preferences: [],
            createdAt: new DateTimeImmutable(),
        );
        $guestRepo->save($guest);
        $this->guestId = GuestModel::where('uuid', (string) $guest->uuid)->value('id');
    }

    private function registerActor(array $overrides = []): Actor
    {
        return Actor::register(
            uuid: $overrides['uuid'] ?? $this->repository->nextIdentity(),
            accountId: array_key_exists('accountId', $overrides) ? $overrides['accountId'] : $this->accountId,
            roles: $overrides['roles'] ?? [$this->guestRole],
            name: $overrides['name'] ?? 'John Doe',
            email: $overrides['email'] ?? 'john@hotel.com',
            password: $overrides['password'] ?? new HashedPassword('$2y$10$somehash'),
            subjectType: array_key_exists('subjectType', $overrides) ? $overrides['subjectType'] : 'guest',
            subjectId: array_key_exists('subjectId', $overrides) ? $overrides['subjectId'] : $this->guestId,
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
        $this->assertTrue($found->hasRole(RoleName::GUEST));
        $this->assertSame('guest', $found->subjectType);
        $this->assertSame($this->guestId, $found->subjectId);
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
    public function itSavesActorWithNullSubject(): void
    {
        $actor = $this->registerActor([
            'roles' => [$this->adminRole],
            'subjectType' => null,
            'subjectId' => null,
        ]);
        $this->repository->save($actor);

        $found = $this->repository->findByUuid($actor->uuid);

        $this->assertTrue($found->hasRole(RoleName::ADMIN));
        $this->assertNull($found->subjectType);
        $this->assertNull($found->subjectId);
    }

    #[Test]
    public function itGeneratesUniqueIdentities(): void
    {
        $id1 = $this->repository->nextIdentity();
        $id2 = $this->repository->nextIdentity();

        $this->assertFalse($id1->equals($id2));
    }

    #[Test]
    public function itSavesSuperAdminWithNullAccount(): void
    {
        $roleRepository = $this->app->make(\Modules\IAM\Domain\Repository\RoleRepository::class);
        $superadminRole = Role::create(uuid: $roleRepository->nextIdentity(), name: RoleName::SUPERADMIN);
        $roleRepository->save($superadminRole);

        $actor = Actor::register(
            uuid: $this->repository->nextIdentity(),
            accountId: null,
            roles: [$superadminRole],
            name: 'Super Admin',
            email: 'super@guesthub.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable(),
        );
        $this->repository->save($actor);

        $found = $this->repository->findByUuid($actor->uuid);

        $this->assertNotNull($found);
        $this->assertNull($found->accountId);
        $this->assertTrue($found->isSuperAdmin());
    }

    #[Test]
    public function itPersistsMultipleRoles(): void
    {
        $actor = $this->registerActor([
            'roles' => [$this->guestRole, $this->adminRole],
        ]);
        $this->repository->save($actor);

        $found = $this->repository->findByUuid($actor->uuid);

        $this->assertCount(2, $found->roles());
        $this->assertTrue($found->hasRole(RoleName::GUEST));
        $this->assertTrue($found->hasRole(RoleName::ADMIN));
    }
}
