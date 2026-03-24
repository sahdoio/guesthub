<?php

declare(strict_types=1);

namespace Tests\Unit\IAM\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Service\EmailUniquenessChecker;
use Modules\IAM\Domain\TypeId;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Actor::class)]
final class ActorTest extends TestCase
{
    private AccountId $accountId;

    private TypeId $typeId;

    private EmailUniquenessChecker $emailChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = AccountId::generate();
        $this->typeId = TypeId::generate();
        $this->emailChecker = $this->createStub(EmailUniquenessChecker::class);
        $this->emailChecker->method('isEmailTaken')->willReturn(false);
    }

    #[Test]
    public function it_creates_a_guest_actor(): void
    {
        $id = ActorId::generate();
        $actor = Actor::register(
            uuid: $id,
            accountId: $this->accountId,
            typeIds: [$this->typeId],
            name: 'John Doe',
            email: 'john@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            userId: 1,
            createdAt: new DateTimeImmutable,
            emailUniquenessChecker: $this->emailChecker,
        );

        $this->assertSame($id, $actor->uuid);
        $this->assertTrue($this->accountId->equals($actor->accountId));
        $this->assertCount(1, $actor->typeIds());
        $this->assertTrue($actor->hasTypeId($this->typeId));
        $this->assertSame('John Doe', $actor->name);
        $this->assertSame('john@hotel.com', $actor->email);
        $this->assertSame(1, $actor->userId);
        $this->assertNull($actor->updatedAt);
    }

    #[Test]
    public function it_creates_an_owner_actor(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            typeIds: [$this->typeId],
            name: 'Hotel Owner',
            email: 'owner@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            userId: 1,
            createdAt: new DateTimeImmutable,
            emailUniquenessChecker: $this->emailChecker,
        );

        $this->assertCount(1, $actor->typeIds());
        $this->assertTrue($actor->hasTypeId($this->typeId));
    }

    #[Test]
    public function it_creates_actor_without_subject(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            typeIds: [$this->typeId],
            name: 'Booking Engine',
            email: 'system@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            userId: null,
            createdAt: new DateTimeImmutable,
            emailUniquenessChecker: $this->emailChecker,
        );

        $this->assertNull($actor->userId);
    }

    #[Test]
    public function it_changes_password(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            typeIds: [$this->typeId],
            name: 'Jane Doe',
            email: 'jane@hotel.com',
            password: new HashedPassword('$2y$10$oldhash'),
            userId: null,
            createdAt: new DateTimeImmutable,
            emailUniquenessChecker: $this->emailChecker,
        );

        $newPassword = new HashedPassword('$2y$10$newhash');
        $actor->changePassword($newPassword);

        $this->assertSame('$2y$10$newhash', $actor->password->value);
        $this->assertNotNull($actor->updatedAt);
    }

    #[Test]
    public function it_validates_type_name(): void
    {
        $this->assertSame('guest', \Modules\IAM\Domain\ValueObject\TypeName::GUEST->value);
        $this->assertSame('superadmin', \Modules\IAM\Domain\ValueObject\TypeName::SUPERADMIN->value);
        $this->assertSame('owner', \Modules\IAM\Domain\ValueObject\TypeName::OWNER->value);
    }

    #[Test]
    public function it_rejects_empty_hashed_password(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HashedPassword('');
    }

    #[Test]
    public function it_creates_super_admin_without_account(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: null,
            typeIds: [$this->typeId],
            name: 'Super Admin',
            email: 'super@guesthub.com',
            password: new HashedPassword('$2y$10$somehash'),
            userId: null,
            createdAt: new DateTimeImmutable,
            emailUniquenessChecker: $this->emailChecker,
        );

        $this->assertTrue($actor->hasTypeId($this->typeId));
        $this->assertNull($actor->accountId);
    }

    #[Test]
    public function it_checks_has_type_id(): void
    {
        $typeId1 = TypeId::generate();
        $typeId2 = TypeId::generate();

        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            typeIds: [$typeId1, $typeId2],
            name: 'Multi Role',
            email: 'multi@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            userId: null,
            createdAt: new DateTimeImmutable,
            emailUniquenessChecker: $this->emailChecker,
        );

        $this->assertTrue($actor->hasTypeId($typeId1));
        $this->assertTrue($actor->hasTypeId($typeId2));
        $this->assertFalse($actor->hasTypeId(TypeId::generate()));
    }

    #[Test]
    public function it_assigns_a_type(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            typeIds: [$this->typeId],
            name: 'Test Actor',
            email: 'test@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            userId: null,
            createdAt: new DateTimeImmutable,
            emailUniquenessChecker: $this->emailChecker,
        );

        $newTypeId = TypeId::generate();
        $actor->assignType($newTypeId);

        $this->assertCount(2, $actor->typeIds());
        $this->assertTrue($actor->hasTypeId($newTypeId));
        $this->assertNotNull($actor->updatedAt);
    }

    #[Test]
    public function it_does_not_duplicate_type_on_assign(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            typeIds: [$this->typeId],
            name: 'Test Actor',
            email: 'test@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            userId: null,
            createdAt: new DateTimeImmutable,
            emailUniquenessChecker: $this->emailChecker,
        );

        $actor->assignType($this->typeId);

        $this->assertCount(1, $actor->typeIds());
    }
}
