<?php

declare(strict_types=1);

namespace Tests\Unit\IAM\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\RoleId;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Actor::class)]
final class ActorTest extends TestCase
{
    private AccountId $accountId;

    private RoleId $roleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = AccountId::generate();
        $this->roleId = RoleId::generate();
    }

    #[Test]
    public function it_creates_a_guest_actor(): void
    {
        $id = ActorId::generate();
        $actor = Actor::register(
            uuid: $id,
            accountId: $this->accountId,
            roleIds: [$this->roleId],
            name: 'John Doe',
            email: 'john@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: 'guest',
            subjectId: 1,
            createdAt: new DateTimeImmutable,
        );

        $this->assertSame($id, $actor->uuid);
        $this->assertTrue($this->accountId->equals($actor->accountId));
        $this->assertCount(1, $actor->roleIds());
        $this->assertTrue($actor->hasRoleId($this->roleId));
        $this->assertSame('John Doe', $actor->name);
        $this->assertSame('john@hotel.com', $actor->email);
        $this->assertSame('guest', $actor->subjectType);
        $this->assertSame(1, $actor->subjectId);
        $this->assertNull($actor->updatedAt);
    }

    #[Test]
    public function it_creates_an_admin_actor(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roleIds: [$this->roleId],
            name: 'Hotel Manager',
            email: 'manager@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable,
        );

        $this->assertCount(1, $actor->roleIds());
        $this->assertTrue($actor->hasRoleId($this->roleId));
    }

    #[Test]
    public function it_creates_actor_without_subject(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roleIds: [$this->roleId],
            name: 'Booking Engine',
            email: 'system@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable,
        );

        $this->assertNull($actor->subjectType);
        $this->assertNull($actor->subjectId);
    }

    #[Test]
    public function it_changes_password(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roleIds: [$this->roleId],
            name: 'Jane Doe',
            email: 'jane@hotel.com',
            password: new HashedPassword('$2y$10$oldhash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable,
        );

        $newPassword = new HashedPassword('$2y$10$newhash');
        $actor->changePassword($newPassword);

        $this->assertSame('$2y$10$newhash', $actor->password->value);
        $this->assertNotNull($actor->updatedAt);
    }

    #[Test]
    public function it_validates_role_name(): void
    {
        $this->assertSame('admin', \Modules\IAM\Domain\ValueObject\RoleName::ADMIN->value);
        $this->assertSame('guest', \Modules\IAM\Domain\ValueObject\RoleName::GUEST->value);
        $this->assertSame('superadmin', \Modules\IAM\Domain\ValueObject\RoleName::SUPERADMIN->value);
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
            roleIds: [$this->roleId],
            name: 'Super Admin',
            email: 'super@guesthub.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable,
        );

        $this->assertTrue($actor->hasRoleId($this->roleId));
        $this->assertNull($actor->accountId);
    }

    #[Test]
    public function it_checks_has_role_id(): void
    {
        $roleId1 = RoleId::generate();
        $roleId2 = RoleId::generate();

        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roleIds: [$roleId1, $roleId2],
            name: 'Multi Role',
            email: 'multi@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable,
        );

        $this->assertTrue($actor->hasRoleId($roleId1));
        $this->assertTrue($actor->hasRoleId($roleId2));
        $this->assertFalse($actor->hasRoleId(RoleId::generate()));
    }

    #[Test]
    public function it_assigns_a_role(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roleIds: [$this->roleId],
            name: 'Test Actor',
            email: 'test@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable,
        );

        $newRoleId = RoleId::generate();
        $actor->assignRole($newRoleId);

        $this->assertCount(2, $actor->roleIds());
        $this->assertTrue($actor->hasRoleId($newRoleId));
        $this->assertNotNull($actor->updatedAt);
    }

    #[Test]
    public function it_does_not_duplicate_role_on_assign(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roleIds: [$this->roleId],
            name: 'Test Actor',
            email: 'test@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable,
        );

        $actor->assignRole($this->roleId);

        $this->assertCount(1, $actor->roleIds());
    }
}
