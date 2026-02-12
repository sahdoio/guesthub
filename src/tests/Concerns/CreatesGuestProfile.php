<?php

declare(strict_types=1);

namespace Tests\Concerns;

use DateTimeImmutable;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;

trait CreatesGuestProfile
{
    protected function createGuestProfile(array $overrides = []): string
    {
        $repository = $this->app->make(GuestProfileRepository::class);

        $profile = GuestProfile::create(
            uuid: $repository->nextIdentity(),
            fullName: $overrides['full_name'] ?? 'John Doe',
            email: $overrides['email'] ?? 'john@hotel.com',
            phone: $overrides['phone'] ?? '+5511999999999',
            document: $overrides['document'] ?? '12345678900',
            loyaltyTier: LoyaltyTier::from($overrides['loyalty_tier'] ?? 'bronze'),
            preferences: $overrides['preferences'] ?? [],
            createdAt: new DateTimeImmutable(),
        );

        $repository->save($profile);

        return (string) $profile->uuid;
    }
}
