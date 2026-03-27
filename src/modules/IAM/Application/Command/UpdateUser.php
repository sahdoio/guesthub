<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class UpdateUser extends BaseData
{
    /**
     * @param  string[]|null  $preferences
     */
    public function __construct(
        public string $userId,
        public ?string $fullName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $loyaltyTier = null,
        public ?array $preferences = null,
    ) {}
}
