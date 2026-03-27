<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class CreateUser extends BaseData
{
    public function __construct(
        public string $fullName,
        public string $email,
        public string $password,
        public string $phone,
        public string $document,
        public string $actorType = 'guest',
        public ?string $loyaltyTier = null,
        public ?string $accountName = null,
        public ?string $accountSlug = null,
    ) {}
}
