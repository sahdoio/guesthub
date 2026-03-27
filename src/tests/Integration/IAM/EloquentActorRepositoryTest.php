<?php

declare(strict_types=1);

namespace Tests\Integration\IAM;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Domain\Account;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Repository\TypeRepository;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\Service\EmailUniquenessChecker;
use Modules\IAM\Domain\Type;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\ValueObject\AccountId;
use Modules\IAM\Domain\ValueObject\ActorId;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Domain\ValueObject\TypeName;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentActorRepository;
use Modules\IAM\Infrastructure\Persistence\Eloquent\UserModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(EloquentActorRepository::class)]
final class EloquentActorRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ActorRepository $repository;

    private TypeRepository $typeRepository;

    private AccountId $accountId;

    private Type $guestType;

    private Type $ownerType;

    private int $guestId;

    private EmailUniquenessChecker $emailChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(ActorRepository::class);
        $this->typeRepository = $this->app->make(TypeRepository::class);

        // Seed types via TypeRepository
        $this->guestType = Type::create(uuid: $this->typeRepository->nextIdentity(), name: TypeName::GUEST);
        $this->typeRepository->save($this->guestType);

        $this->ownerType = Type::create(uuid: $this->typeRepository->nextIdentity(), name: TypeName::OWNER);
        $this->typeRepository->save($this->ownerType);

        // Seed account
        $accountRepository = $this->app->make(AccountRepository::class);
        $account = Account::create(
            uuid: $accountRepository->nextIdentity(),
            name: 'Test Hotel',
            slug: 'test-hotel',
            createdAt: new DateTimeImmutable,
        );
        $accountRepository->save($account);
        $this->accountId = $account->uuid;

        // Set tenant context
        $numericAccountId = (int) AccountModel::where('uuid', $account->uuid->value)->value('id');
        $this->app->make(TenantContext::class)->set($numericAccountId);

        // Seed a user for FK reference
        $userRepo = $this->app->make(UserRepository::class);
        $user = User::create(
            uuid: $userRepo->nextIdentity(),
            fullName: 'John Doe',
            email: 'john@hotel.com',
            phone: '5511999999999',
            document: 'DOC123',
            loyaltyTier: LoyaltyTier::BRONZE,
            preferences: [],
            createdAt: new DateTimeImmutable,
            hashedPassword: 'hashed_default',
            actorType: 'guest',
            emailUniquenessChecker: $this->app->make(\Modules\IAM\Domain\Service\UserEmailUniquenessChecker::class),
        );
        $userRepo->save($user);
        $this->guestId = UserModel::where('uuid', (string) $user->uuid)->value('id');

        $this->emailChecker = $this->createStub(EmailUniquenessChecker::class);
        $this->emailChecker->method('isEmailTaken')->willReturn(false);
    }

    private function registerActor(array $overrides = []): Actor
    {
        return Actor::register(
            uuid: $overrides['uuid'] ?? $this->repository->nextIdentity(),
            accountId: array_key_exists('accountId', $overrides) ? $overrides['accountId'] : $this->accountId,
            typeIds: $overrides['typeIds'] ?? [$this->guestType->uuid],
            name: $overrides['name'] ?? 'John Doe',
            email: $overrides['email'] ?? 'john@hotel.com',
            password: $overrides['password'] ?? new HashedPassword('$2y$10$somehash'),
            userId: array_key_exists('userId', $overrides) ? $overrides['userId'] : $this->guestId,
            createdAt: $overrides['createdAt'] ?? new DateTimeImmutable,
            emailUniquenessChecker: $this->emailChecker,
        );
    }

    #[Test]
    public function it_saves_and_finds_by_uuid(): void
    {
        $actor = $this->registerActor();
        $this->repository->save($actor);

        $found = $this->repository->findByUuid($actor->uuid);

        $this->assertNotNull($found);
        $this->assertTrue($actor->uuid->equals($found->uuid));
        $this->assertSame('John Doe', $found->name);
        $this->assertSame('john@hotel.com', $found->email);
        $this->assertTrue($found->hasTypeId($this->guestType->uuid));
        $this->assertSame($this->guestId, $found->userId);
    }

    #[Test]
    public function it_returns_null_for_unknown_uuid(): void
    {
        $this->assertNull($this->repository->findByUuid(ActorId::generate()));
    }

    #[Test]
    public function it_finds_by_email(): void
    {
        $actor = $this->registerActor(['email' => 'jane@hotel.com']);
        $this->repository->save($actor);

        $found = $this->repository->findByEmail('jane@hotel.com');

        $this->assertNotNull($found);
        $this->assertSame('jane@hotel.com', $found->email);
    }

    #[Test]
    public function it_returns_null_for_unknown_email(): void
    {
        $this->assertNull($this->repository->findByEmail('nonexistent@hotel.com'));
    }

    #[Test]
    public function it_updates_existing_actor(): void
    {
        $actor = $this->registerActor();
        $this->repository->save($actor);

        $actor->changePassword(new HashedPassword('$2y$10$newhash'));
        $this->repository->save($actor);

        $found = $this->repository->findByUuid($actor->uuid);

        $this->assertSame('$2y$10$newhash', $found->password->value);
    }

    #[Test]
    public function it_saves_actor_with_null_user(): void
    {
        $actor = $this->registerActor([
            'typeIds' => [$this->ownerType->uuid],
            'userId' => null,
        ]);
        $this->repository->save($actor);

        $found = $this->repository->findByUuid($actor->uuid);

        $this->assertTrue($found->hasTypeId($this->ownerType->uuid));
        $this->assertNull($found->userId);
    }

    #[Test]
    public function it_generates_unique_identities(): void
    {
        $id1 = $this->repository->nextIdentity();
        $id2 = $this->repository->nextIdentity();

        $this->assertFalse($id1->equals($id2));
    }

    #[Test]
    public function it_saves_super_admin_with_null_account(): void
    {
        $superadminType = Type::create(uuid: $this->typeRepository->nextIdentity(), name: TypeName::SUPERADMIN);
        $this->typeRepository->save($superadminType);

        $actor = Actor::register(
            uuid: $this->repository->nextIdentity(),
            accountId: null,
            typeIds: [$superadminType->uuid],
            name: 'Super Admin',
            email: 'super@guesthub.com',
            password: new HashedPassword('$2y$10$somehash'),
            userId: null,
            createdAt: new DateTimeImmutable,
            emailUniquenessChecker: $this->emailChecker,
        );
        $this->repository->save($actor);

        $found = $this->repository->findByUuid($actor->uuid);

        $this->assertNotNull($found);
        $this->assertNull($found->accountId);
        $this->assertTrue($found->hasTypeId($superadminType->uuid));
    }

    #[Test]
    public function it_persists_multiple_types(): void
    {
        $actor = $this->registerActor([
            'typeIds' => [$this->guestType->uuid, $this->ownerType->uuid],
        ]);
        $this->repository->save($actor);

        $found = $this->repository->findByUuid($actor->uuid);

        $this->assertCount(2, $found->typeIds());
        $this->assertTrue($found->hasTypeId($this->guestType->uuid));
        $this->assertTrue($found->hasTypeId($this->ownerType->uuid));
    }
}
