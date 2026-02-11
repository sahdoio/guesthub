<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\ValueObject;

use Modules\Shared\Domain\ValueObject;

final class HashedPassword extends ValueObject
{
    public function __construct(
        public readonly string $value,
    ) {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Hashed password cannot be empty.');
        }
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }
}
