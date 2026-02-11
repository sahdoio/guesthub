<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Service;

use Modules\IAM\Domain\ValueObject\HashedPassword;

interface PasswordHasher
{
    public function hash(string $plainPassword): HashedPassword;

    public function verify(string $plainPassword, HashedPassword $hashedPassword): bool;
}
