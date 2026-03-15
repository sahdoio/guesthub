<?php

declare(strict_types=1);

namespace Tests\Unit\IAM\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\RoleId;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\IAM\Infrastructure\Persistence\ActorReflector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActorReflector::class)]
final class ActorReflectorTest extends TestCase
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
    public function it_reconstructs_a_guest_actor(): void
    {
        $uuid = ActorId::generate();
        $createdAt = new DateTimeImmutable('2026-01-15 10:00:00');

        $actor = ActorReflector::reconstruct(
            uuid: $uuid,
            accountId: $this->accountId,
            roleIds: [$this->roleId],
            name: 'John Doe',
            email: 'john@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            subjectType: 'guest',
            subjectId: 1,
            createdAt: $createdAt,
            updatedAt: null,
        );

        $this->assertInstanceOf(Actor::class, $actor);
        $this->assertTrue($uuid->equals($actor->uuid));
        $this->assertTrue($this->accountId->equals($actor->accountId));
        $this->assertCount(1, $actor->roleIds());
        $this->assertTrue($actor->hasRoleId($this->roleId));
        $this->assertSame('John Doe', $actor->name);
        $this->assertSame('john@hotel.com', $actor->email);
        $this->assertSame('$2y$10$somehash', $actor->password->value);
        $this->assertSame('guest', $actor->subjectType);
        $this->assertSame(1, $actor->subjectId);
        $this->assertSame($createdAt, $actor->createdAt);
        $this->assertNull($actor->updatedAt);
    }

    #[Test]
    public function it_reconstructs_an_admin_actor(): void
    {
        $actor = ActorReflector::reconstruct(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roleIds: [$this->roleId],
            name: 'Hotel Manager',
            email: 'manager@hotel.com',
            password: new HashedPassword('$2y$10$hash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable,
            updatedAt: null,
        );

        $this->assertCount(1, $actor->roleIds());
        $this->assertTrue($actor->hasRoleId($this->roleId));
    }

    #[Test]
    public function it_reconstructs_with_updated_at(): void
    {
        $actor = ActorReflector::reconstruct(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roleIds: [$this->roleId],
            name: 'Booking Engine',
            email: 'system@hotel.com',
            password: new HashedPassword('$2y$10$hash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable,
            updatedAt: new DateTimeImmutable,
        );

        $this->assertNull($actor->subjectType);
        $this->assertNull($actor->subjectId);
        $this->assertNotNull($actor->updatedAt);
    }

    #[Test]
    public function it_does_not_record_domain_events(): void
    {
        $actor = ActorReflector::reconstruct(
            uuid: ActorId::generate(),
            accountId: $this->accountId,
            roleIds: [$this->roleId],
            name: 'Jane',
            email: 'jane@hotel.com',
            password: new HashedPassword('$2y$10$hash'),
            subjectType: null,
            subjectId: null,
            createdAt: new DateTimeImmutable,
            updatedAt: null,
        );

        $this->assertEmpty($actor->pullDomainEvents());
    }
}
