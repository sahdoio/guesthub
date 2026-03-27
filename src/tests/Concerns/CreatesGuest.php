<?php

declare(strict_types=1);

namespace Tests\Concerns;

use DateTimeImmutable;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\Service\UserEmailUniquenessChecker;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;

trait CreatesGuest
{
    protected function createGuest(array $overrides = []): string
    {
        $repository = $this->app->make(UserRepository::class);
        $emailChecker = $this->app->make(UserEmailUniquenessChecker::class);

        $guest = User::create(
            uuid: $repository->nextIdentity(),
            fullName: $overrides['full_name'] ?? 'John Doe',
            email: $overrides['email'] ?? 'john@hotel.com',
            phone: $overrides['phone'] ?? '5511999999999',
            document: $overrides['document'] ?? '12345678900',
            loyaltyTier: LoyaltyTier::from($overrides['loyalty_tier'] ?? 'bronze'),
            preferences: $overrides['preferences'] ?? [],
            createdAt: new DateTimeImmutable,
            hashedPassword: $overrides['hashed_password'] ?? 'hashed_default',
            actorType: $overrides['actor_type'] ?? 'guest',
            emailUniquenessChecker: $emailChecker,
        );

        $repository->save($guest);

        return (string) $guest->uuid;
    }
}
