<?php

declare(strict_types=1);

namespace Tests\Unit\Guest\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\Service\UserEmailUniquenessChecker;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
final class GuestTest extends TestCase
{
    private function emailChecker(): UserEmailUniquenessChecker
    {
        return new class implements UserEmailUniquenessChecker
        {
            public function isEmailTaken(string $email): bool
            {
                return false;
            }
        };
    }

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
            hashedPassword: $overrides['hashedPassword'] ?? 'hashed_default',
            actorType: $overrides['actorType'] ?? 'guest',
            emailUniquenessChecker: $this->emailChecker(),
        );
    }

    #[Test]
    public function itCreatesAGuestProfile(): void
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
            hashedPassword: 'hashed_default',
            actorType: 'guest',
            emailUniquenessChecker: $this->emailChecker(),
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
    public function itUpdatesContactInfo(): void
    {
        $profile = $this->createProfile();

        $profile->updateContactInfo('John Smith', 'john@hotel.com', '5521888888888');

        $this->assertSame('John Smith', $profile->fullName);
        $this->assertSame('john@hotel.com', $profile->email);
        $this->assertSame('5521888888888', $profile->phone);
        $this->assertNotNull($profile->updatedAt);
    }

    #[Test]
    public function itChangesLoyaltyTier(): void
    {
        $profile = $this->createProfile();

        $profile->changeLoyaltyTier(LoyaltyTier::GOLD);

        $this->assertSame(LoyaltyTier::GOLD, $profile->loyaltyTier);
        $this->assertNotNull($profile->updatedAt);
    }

    #[Test]
    public function itSetsPreferences(): void
    {
        $profile = $this->createProfile();

        $profile->setPreferences(['ocean_view', 'king_bed', 'minibar']);

        $this->assertSame(['ocean_view', 'king_bed', 'minibar'], $profile->preferences);
        $this->assertNotNull($profile->updatedAt);
    }

    #[Test]
    public function entityEqualityById(): void
    {
        $id = UserId::generate();

        $a = $this->createProfile(['uuid' => $id]);
        $b = $this->createProfile(['uuid' => $id, 'fullName' => 'Different Name']);

        $this->assertTrue($a->equals($b));
    }

    #[Test]
    public function entityInequalityById(): void
    {
        $a = $this->createProfile();
        $b = $this->createProfile();

        $this->assertFalse($a->equals($b));
    }
}
