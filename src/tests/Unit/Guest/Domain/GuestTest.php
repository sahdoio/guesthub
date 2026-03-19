<?php

declare(strict_types=1);

namespace Tests\Unit\Guest\Domain;

use DateTimeImmutable;
use Modules\User\Domain\User;
use Modules\User\Domain\UserId;
use Modules\User\Domain\ValueObject\LoyaltyTier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
final class GuestTest extends TestCase
{
    private function createProfile(array $overrides = []): User
    {
        return User::create(
            uuid: $overrides['uuid'] ?? UserId::generate(),
            fullName: $overrides['fullName'] ?? 'Jane Doe',
            email: $overrides['email'] ?? 'jane@hotel.com',
            phone: $overrides['phone'] ?? '5511999999999',
            document: $overrides['document'] ?? 'ABC123456',
            loyaltyTier: $overrides['loyaltyTier'] ?? LoyaltyTier::BRONZE,
            preferences: $overrides['preferences'] ?? [],
            createdAt: $overrides['createdAt'] ?? new DateTimeImmutable,
        );
    }

    #[Test]
    public function it_creates_a_guest_profile(): void
    {
        $id = UserId::generate();

        $profile = User::create(
            uuid: $id,
            fullName: 'Jane Doe',
            email: 'jane@hotel.com',
            phone: '5511999999999',
            document: 'ABC123456',
            loyaltyTier: LoyaltyTier::BRONZE,
            preferences: ['late_checkout', 'high_floor'],
            createdAt: new DateTimeImmutable,
        );

        $this->assertTrue($profile->id()->equals($id));
        $this->assertSame('Jane Doe', $profile->fullName);
        $this->assertSame('jane@hotel.com', $profile->email);
        $this->assertSame('5511999999999', $profile->phone);
        $this->assertSame('ABC123456', $profile->document);
        $this->assertSame(LoyaltyTier::BRONZE, $profile->loyaltyTier);
        $this->assertSame(['late_checkout', 'high_floor'], $profile->preferences);
        $this->assertNull($profile->updatedAt);
    }

    #[Test]
    public function it_updates_contact_info(): void
    {
        $profile = $this->createProfile();

        $profile->updateContactInfo('John Smith', 'john@hotel.com', '5521888888888');

        $this->assertSame('John Smith', $profile->fullName);
        $this->assertSame('john@hotel.com', $profile->email);
        $this->assertSame('5521888888888', $profile->phone);
        $this->assertNotNull($profile->updatedAt);
    }

    #[Test]
    public function it_changes_loyalty_tier(): void
    {
        $profile = $this->createProfile();

        $profile->changeLoyaltyTier(LoyaltyTier::GOLD);

        $this->assertSame(LoyaltyTier::GOLD, $profile->loyaltyTier);
        $this->assertNotNull($profile->updatedAt);
    }

    #[Test]
    public function it_sets_preferences(): void
    {
        $profile = $this->createProfile();

        $profile->setPreferences(['ocean_view', 'king_bed', 'minibar']);

        $this->assertSame(['ocean_view', 'king_bed', 'minibar'], $profile->preferences);
        $this->assertNotNull($profile->updatedAt);
    }

    #[Test]
    public function entity_equality_by_id(): void
    {
        $id = UserId::generate();

        $a = $this->createProfile(['uuid' => $id]);
        $b = $this->createProfile(['uuid' => $id, 'fullName' => 'Different Name']);

        $this->assertTrue($a->equals($b));
    }

    #[Test]
    public function entity_inequality_by_id(): void
    {
        $a = $this->createProfile();
        $b = $this->createProfile();

        $this->assertFalse($a->equals($b));
    }
}
