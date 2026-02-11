<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Services;

use Illuminate\Support\Facades\Hash;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\ValueObject\HashedPassword;

final class BcryptPasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): HashedPassword
    {
        return new HashedPassword(Hash::make($plainPassword));
    }

    public function verify(string $plainPassword, HashedPassword $hashedPassword): bool
    {
        return Hash::check($plainPassword, $hashedPassword->value);
    }
}
