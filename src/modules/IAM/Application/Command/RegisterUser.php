<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class RegisterUser extends BaseData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $phone,
        public string $document,
    ) {}
}
