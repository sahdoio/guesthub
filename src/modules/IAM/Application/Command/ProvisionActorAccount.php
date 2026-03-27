<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class ProvisionActorAccount extends BaseData
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $email,
        public string $hashedPassword,
        public string $actorType,
        public ?string $accountName = null,
        public ?string $accountSlug = null,
    ) {}
}
