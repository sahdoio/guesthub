<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class AuthenticateActor extends BaseData
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
