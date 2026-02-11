<?php

declare(strict_types=1);

namespace Tests\Unit\Guest\Domain;

use DateTimeImmutable;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use PHPUnit\Framework\TestCase;

final class GuestProfileTest extends TestCase
{
    private function createProfile(array $overrides = []): GuestProfile
    {
        return GuestProfile::create(
            uuid: $overrides['uuid'] ?? GuestProfileId::generate(),
            fullName: $overrides['fullName'] ?? 'Jane Doe',
            email: $overrides['email'] ?? 'jane@hotel.com',
            phone: $overrides['phone'] ?? '+5511999999999',
            document: $overrides['document'] ?? 'ABC123456',
            loyaltyTier: $overrides['loyaltyTier'] ?? LoyaltyTier::BRONZE,
            preferences: $overrides['preferences'] ?? [],
            createdAt: $overrides['createdAt'] ?? new DateTimeImmutable(),
        );
    }

    public function test_it_creates_a_guest_profile(): void
    {
        $id = GuestProfileId::generate();

        $profile = GuestProfile::create(
            uuid: $id,
            fullName: 'Jane Doe',
            email: 'jane@hotel.com',
            phone: '+5511999999999',
            document: 'ABC123456',
            loyaltyTier: LoyaltyTier::BRONZE,
            preferences: ['late_checkout', 'high_floor'],
            createdAt: new DateTimeImmutable(),
        );

        $this->assertTrue($profile->id()->equals($id));
        $this->assertSame('Jane Doe', $profile->fullName);
        $this->assertSame('jane@hotel.com', $profile->email);
        $this->assertSame('+5511999999999', $profile->phone);
        $this->assertSame('ABC123456', $profile->document);
        $this->assertSame(LoyaltyTier::BRONZE, $profile->loyaltyTier);
        $this->assertSame(['late_checkout', 'high_floor'], $profile->preferences);
        $this->assertNull($profile->updatedAt);
    }

    public function test_it_updates_contact_info(): void
    {
        $profile = $this->createProfile();

        $profile->updateContactInfo('John Smith', 'john@hotel.com', '+5521888888888');

        $this->assertSame('John Smith', $profile->fullName);
        $this->assertSame('john@hotel.com', $profile->email);
        $this->assertSame('+5521888888888', $profile->phone);
        $this->assertNotNull($profile->updatedAt);
    }

    public function test_it_changes_loyalty_tier(): void
    {
        $profile = $this->createProfile();

        $profile->changeLoyaltyTier(LoyaltyTier::GOLD);

        $this->assertSame(LoyaltyTier::GOLD, $profile->loyaltyTier);
        $this->assertNotNull($profile->updatedAt);
    }

    public function test_it_sets_preferences(): void
    {
        $profile = $this->createProfile();

        $profile->setPreferences(['ocean_view', 'king_bed', 'minibar']);

        $this->assertSame(['ocean_view', 'king_bed', 'minibar'], $profile->preferences);
        $this->assertNotNull($profile->updatedAt);
    }

    public function test_entity_equality_by_id(): void
    {
        $id = GuestProfileId::generate();

        $a = $this->createProfile(['uuid' => $id]);
        $b = $this->createProfile(['uuid' => $id, 'fullName' => 'Different Name']);

        $this->assertTrue($a->equals($b));
    }

    public function test_entity_inequality_by_id(): void
    {
        $a = $this->createProfile();
        $b = $this->createProfile();

        $this->assertFalse($a->equals($b));
    }
}
