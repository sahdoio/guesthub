<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Integration;

use Modules\Guest\Infrastructure\Integration\GuestProfileApi;
use Modules\IAM\Domain\Service\GuestProfileGateway;

final readonly class GuestProfileGatewayAdapter implements GuestProfileGateway
{
    public function __construct(
        private GuestProfileApi $guestProfileApi,
    ) {}

    public function create(string $name, string $email, string $phone, string $document): string
    {
        return $this->guestProfileApi->create(
            name: $name,
            email: $email,
            phone: $phone,
            document: $document,
        );
    }
}
