<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Service;

interface UserEmailUniquenessChecker
{
    public function isEmailTaken(string $email): bool;
}
