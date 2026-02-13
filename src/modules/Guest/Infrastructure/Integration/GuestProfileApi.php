<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Integration;

use Modules\Guest\Application\Command\CreateGuestProfile;
use Modules\Guest\Application\Command\CreateGuestProfileHandler;

final readonly class GuestProfileApi
{
    public function __construct(
        private CreateGuestProfileHandler $createHandler,
    ) {}

    public function create(string $name, string $email, string $phone, string $document): string
    {
        $id = $this->createHandler->handle(new CreateGuestProfile(
            fullName: $name,
            email: $email,
            phone: $phone,
            document: $document,
        ));

        return (string) $id;
    }
}
