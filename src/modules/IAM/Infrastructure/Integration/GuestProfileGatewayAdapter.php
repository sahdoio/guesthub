<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Integration;

use Illuminate\Support\Facades\DB;
use Modules\Guest\Application\Command\CreateGuestProfile;
use Modules\Guest\Application\Command\CreateGuestProfileHandler;
use Modules\IAM\Domain\Service\GuestProfileGateway;

final class GuestProfileGatewayAdapter implements GuestProfileGateway
{
    public function __construct(
        private readonly CreateGuestProfileHandler $handler,
    ) {}

    public function create(string $name, string $email, string $phone, string $document): int
    {
        $uuid = $this->handler->handle(new CreateGuestProfile(
            fullName: $name,
            email: $email,
            phone: $phone,
            document: $document,
            loyaltyTier: 'bronze',
            preferences: [],
        ));

        return (int) DB::table('guest_profiles')->where('uuid', $uuid->value)->value('id');
    }
}
