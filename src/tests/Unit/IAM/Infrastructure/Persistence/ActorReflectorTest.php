<?php

declare(strict_types=1);

namespace Tests\Unit\IAM\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\ValueObject\ActorType;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\IAM\Infrastructure\Persistence\ActorReflector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActorReflector::class)]
final class ActorReflectorTest extends TestCase
{
    #[Test]
    public function itReconstructsAGuestActor(): void
    {
        $uuid = ActorId::generate();
        $createdAt = new DateTimeImmutable('2026-01-15 10:00:00');

        $actor = ActorReflector::reconstruct(
            uuid: $uuid,
            type: ActorType::GUEST,
            name: 'John Doe',
            email: 'john@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            profileType: 'guest',
            profileId: '019c57c1-9ff8-71c5-9155-26020ca798ed',
            createdAt: $createdAt,
            updatedAt: null,
        );

        $this->assertInstanceOf(Actor::class, $actor);
        $this->assertTrue($uuid->equals($actor->uuid));
        $this->assertSame(ActorType::GUEST, $actor->type);
        $this->assertSame('John Doe', $actor->name);
        $this->assertSame('john@hotel.com', $actor->email);
        $this->assertSame('$2y$10$somehash', $actor->password->value);
        $this->assertSame('guest', $actor->profileType);
        $this->assertSame('019c57c1-9ff8-71c5-9155-26020ca798ed', $actor->profileId);
        $this->assertSame($createdAt, $actor->createdAt);
        $this->assertNull($actor->updatedAt);
    }

    #[Test]
    public function itReconstructsASystemActor(): void
    {
        $actor = ActorReflector::reconstruct(
            uuid: ActorId::generate(),
            type: ActorType::SYSTEM,
            name: 'Booking Engine',
            email: 'system@hotel.com',
            password: new HashedPassword('$2y$10$hash'),
            profileType: null,
            profileId: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
        );

        $this->assertSame(ActorType::SYSTEM, $actor->type);
        $this->assertNull($actor->profileType);
        $this->assertNull($actor->profileId);
        $this->assertNotNull($actor->updatedAt);
    }

    #[Test]
    public function itDoesNotRecordDomainEvents(): void
    {
        $actor = ActorReflector::reconstruct(
            uuid: ActorId::generate(),
            type: ActorType::GUEST,
            name: 'Jane',
            email: 'jane@hotel.com',
            password: new HashedPassword('$2y$10$hash'),
            profileType: null,
            profileId: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: null,
        );

        $this->assertEmpty($actor->pullDomainEvents());
    }
}
