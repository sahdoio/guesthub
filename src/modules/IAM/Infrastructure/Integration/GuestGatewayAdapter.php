<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Integration;

use Modules\Guest\Infrastructure\Integration\GuestApi;
use Modules\IAM\Domain\Service\GuestGateway;

final readonly class GuestGatewayAdapter implements GuestGateway
{
    public function __construct(
        private GuestApi $guestApi,
    ) {}

    public function create(string $name, string $email, string $phone, string $document): int
    {
        return $this->guestApi->create(
            name: $name,
            email: $email,
            phone: $phone,
            document: $document,
        );
    }
}
