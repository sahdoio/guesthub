<?php

declare(strict_types=1);

namespace Tests\Concerns;

use DateTimeImmutable;
use Modules\Guest\Domain\Guest;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;

trait CreatesGuest
{
    protected function createGuest(array $overrides = []): string
    {
        $repository = $this->app->make(GuestRepository::class);

        $guest = Guest::create(
            uuid: $repository->nextIdentity(),
            fullName: $overrides['full_name'] ?? 'John Doe',
            email: $overrides['email'] ?? 'john@hotel.com',
            phone: $overrides['phone'] ?? '+5511999999999',
            document: $overrides['document'] ?? '12345678900',
            loyaltyTier: LoyaltyTier::from($overrides['loyalty_tier'] ?? 'bronze'),
            preferences: $overrides['preferences'] ?? [],
            createdAt: new DateTimeImmutable(),
        );

        $repository->save($guest);

        return (string) $guest->uuid;
    }
}
