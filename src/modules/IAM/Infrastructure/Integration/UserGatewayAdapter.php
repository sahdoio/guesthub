<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Integration;

use Modules\IAM\Infrastructure\Integration\UserApi;
use Modules\IAM\Domain\Service\UserGateway;

final readonly class UserGatewayAdapter implements UserGateway
{
    public function __construct(
        private UserApi $userApi,
    ) {}

    public function create(string $name, string $email, string $phone, string $document, ?string $loyaltyTier = null): int
    {
        return $this->userApi->create(
            name: $name,
            email: $email,
            phone: $phone,
            document: $document,
            loyaltyTier: $loyaltyTier,
        );
    }
}
