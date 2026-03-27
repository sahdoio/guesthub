<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class AuthenticationResult extends BaseData
{
    public function __construct(
        public string $token,
        public string $actorId,
    ) {}
}
