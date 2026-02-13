<?php

declare(strict_types=1);

namespace Tests\Unit\Guest\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Guest\Infrastructure\Persistence\GuestProfileReflector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GuestProfileReflector::class)]
final class GuestProfileReflectorTest extends TestCase
{
    #[Test]
    public function itReconstructsAGuestProfile(): void
    {
        $uuid = GuestProfileId::generate();
        $createdAt = new DateTimeImmutable('2026-01-15 10:00:00');

        $profile = GuestProfileReflector::reconstruct(
            uuid: $uuid,
            fullName: 'Alice Johnson',
            email: 'alice@hotel.com',
            phone: '+5511999999999',
            document: 'ABC123',
            loyaltyTier: LoyaltyTier::GOLD,
            preferences: ['late_checkout', 'high_floor'],
            createdAt: $createdAt,
            updatedAt: null,
        );

        $this->assertInstanceOf(GuestProfile::class, $profile);
        $this->assertTrue($uuid->equals($profile->uuid));
        $this->assertSame('Alice Johnson', $profile->fullName);
        $this->assertSame('alice@hotel.com', $profile->email);
        $this->assertSame('+5511999999999', $profile->phone);
        $this->assertSame('ABC123', $profile->document);
        $this->assertSame(LoyaltyTier::GOLD, $profile->loyaltyTier);
        $this->assertSame(['late_checkout', 'high_floor'], $profile->preferences);
        $this->assertSame($createdAt, $profile->createdAt);
        $this->assertNull($profile->updatedAt);
    }

    #[Test]
    public function itReconstructsWithUpdatedAt(): void
    {
        $updatedAt = new DateTimeImmutable('2026-02-01 15:30:00');

        $profile = GuestProfileReflector::reconstruct(
            uuid: GuestProfileId::generate(),
            fullName: 'Bob',
            email: 'bob@hotel.com',
            phone: '+5511888888888',
            document: 'DEF456',
            loyaltyTier: LoyaltyTier::BRONZE,
            preferences: [],
            createdAt: new DateTimeImmutable('2026-01-01'),
            updatedAt: $updatedAt,
        );

        $this->assertSame($updatedAt, $profile->updatedAt);
    }

    #[Test]
    public function itDoesNotRecordDomainEvents(): void
    {
        $profile = GuestProfileReflector::reconstruct(
            uuid: GuestProfileId::generate(),
            fullName: 'Carol',
            email: 'carol@hotel.com',
            phone: '+5511777777777',
            document: 'GHI789',
            loyaltyTier: LoyaltyTier::PLATINUM,
            preferences: [],
            createdAt: new DateTimeImmutable(),
            updatedAt: null,
        );

        $this->assertEmpty($profile->pullDomainEvents());
    }
}
