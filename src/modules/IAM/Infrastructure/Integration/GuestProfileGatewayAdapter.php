<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Integration;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Domain\Service\GuestProfileGateway;

final readonly class GuestProfileGatewayAdapter implements GuestProfileGateway
{
    public function __construct(
        private GuestProfileRepository $repository,
    ) {}

    public function create(string $name, string $email, string $phone, string $document): int
    {
        $profile = GuestProfile::create(
            uuid: $this->repository->nextIdentity(),
            fullName: $name,
            email: $email,
            phone: $phone,
            document: $document,
            loyaltyTier: LoyaltyTier::BRONZE,
            preferences: [],
            createdAt: new DateTimeImmutable(),
        );

        $this->repository->save($profile);

        return (int) DB::table('guest_profiles')->where('uuid', (string) $profile->uuid)->value('id');
    }
}
