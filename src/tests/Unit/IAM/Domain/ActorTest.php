<?php

declare(strict_types=1);

namespace Tests\Unit\IAM\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\ValueObject\ActorType;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ActorTest extends TestCase
{
    #[Test]
    public function it_creates_a_guest_actor(): void
    {
        $id = ActorId::generate();
        $actor = Actor::register(
            uuid: $id,
            type: ActorType::GUEST,
            name: 'John Doe',
            email: 'john@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            profileType: 'guest',
            profileId: '019c57c1-9ff8-71c5-9155-26020ca798ed',
            createdAt: new DateTimeImmutable(),
        );

        $this->assertSame($id, $actor->uuid);
        $this->assertSame(ActorType::GUEST, $actor->type);
        $this->assertSame('John Doe', $actor->name);
        $this->assertSame('john@hotel.com', $actor->email);
        $this->assertSame('guest', $actor->profileType);
        $this->assertSame('019c57c1-9ff8-71c5-9155-26020ca798ed', $actor->profileId);
        $this->assertNull($actor->updatedAt);
    }

    #[Test]
    public function it_creates_a_system_actor(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            type: ActorType::SYSTEM,
            name: 'Booking Engine',
            email: 'system@hotel.com',
            password: new HashedPassword('$2y$10$somehash'),
            profileType: null,
            profileId: null,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertSame(ActorType::SYSTEM, $actor->type);
        $this->assertNull($actor->profileType);
        $this->assertNull($actor->profileId);
    }

    #[Test]
    public function it_changes_password(): void
    {
        $actor = Actor::register(
            uuid: ActorId::generate(),
            type: ActorType::GUEST,
            name: 'Jane Doe',
            email: 'jane@hotel.com',
            password: new HashedPassword('$2y$10$oldhash'),
            profileType: null,
            profileId: null,
            createdAt: new DateTimeImmutable(),
        );

        $newPassword = new HashedPassword('$2y$10$newhash');
        $actor->changePassword($newPassword);

        $this->assertSame('$2y$10$newhash', $actor->password->value);
        $this->assertNotNull($actor->updatedAt);
    }

    #[Test]
    public function it_validates_actor_type(): void
    {
        $this->assertSame('guest', ActorType::GUEST->value);
        $this->assertSame('system', ActorType::SYSTEM->value);
    }

    #[Test]
    public function it_rejects_empty_hashed_password(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HashedPassword('');
    }
}
