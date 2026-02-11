<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Service;

interface GuestProfileGateway
{
    public function create(string $name, string $email, string $phone, string $document): int;
}
