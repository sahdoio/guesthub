<?php

declare(strict_types=1);

namespace Tests\Unit\IAM\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Role;
use Modules\IAM\Domain\RoleId;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\IAM\Domain\ValueObject\RoleName;
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
    public function itCreatesAGuestActor(): void
    {
        $id = ActorId::generate();
        $actor = Actor::register(
            uuid: $id,
            accountId: $this->accountId,
            roles: [Role::create(uuid: $this->roleId, name: RoleName::GUEST)],
            name: 'John Doe',
            email: 'john@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: 'guest',
            subjectId: 1,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertSame($id, $actor->uuid);
        $this->assertTrue($this->accountId->equals($actor->accountId));
        $this->assertCount(1, $actor->roles());
        $this->assertSame(RoleName::GUEST, $actor->roles()[0]->name);
        $this->assertSame('John Doe', $actor->name);
        $this->assertSame('john@hotel.com', $actor->email);
        $this->assertSame('guest', $actor->subjectType);
        $this->assertSame(1, $actor->subjectId);
        $this->assertNull($actor->updatedAt);
        $this->assertFalse($actor->isAdmin());
    }

    #[Test]
    public function itCreatesAnAdminActor(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roles: [Role::create(uuid: $this->roleId, name: RoleName::ADMIN)],
            name: 'Hotel Manager',
            email: 'manager@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertCount(1, $actor->roles());
        $this->assertSame(RoleName::ADMIN, $actor->roles()[0]->name);
        $this->assertTrue($actor->isAdmin());
    }

    #[Test]
    public function itCreatesActorWithoutSubject(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roles: [Role::create(uuid: $this->roleId, name: RoleName::GUEST)],
            name: 'Booking Engine',
            email: 'system@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertNull($actor->subjectType);
        $this->assertNull($actor->subjectId);
    }

    #[Test]
    public function itChangesPassword(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roles: [Role::create(uuid: $this->roleId, name: RoleName::GUEST)],
            name: 'Jane Doe',
            email: 'jane@hotel.com',
            password: new HashedPassword('$2y$10$oldhash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable(),
        );

        $newPassword = new HashedPassword('$2y$10$newhash');
        $actor->changePassword($newPassword);

        $this->assertSame('$2y$10$newhash', $actor->password->value);
        $this->assertNotNull($actor->updatedAt);
    }

    #[Test]
    public function itValidatesRoleName(): void
    {
        $this->assertSame('admin', RoleName::ADMIN->value);
        $this->assertSame('guest', RoleName::GUEST->value);
        $this->assertSame('superadmin', RoleName::SUPERADMIN->value);
    }

    #[Test]
    public function itRejectsEmptyHashedPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HashedPassword('');
    }

    #[Test]
    public function itIdentifiesSuperAdmin(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: null,
            roles: [Role::create(uuid: $this->roleId, name: RoleName::SUPERADMIN)],
            name: 'Super Admin',
            email: 'super@guesthub.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertTrue($actor->isSuperAdmin());
        $this->assertNull($actor->accountId);
    }

    #[Test]
    public function itChecksHasRole(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roles: [
                Role::create(uuid: RoleId::generate(), name: RoleName::ADMIN),
                Role::create(uuid: RoleId::generate(), name: RoleName::GUEST),
            ],
            name: 'Multi Role',
            email: 'multi@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertTrue($actor->hasRole(RoleName::ADMIN));
        $this->assertTrue($actor->hasRole(RoleName::GUEST));
        $this->assertFalse($actor->hasRole(RoleName::SUPERADMIN));
    }
}
