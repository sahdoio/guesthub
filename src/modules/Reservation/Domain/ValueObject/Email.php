<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\ValueObject;

use Modules\Shared\Domain\ValueObject;

final class Email extends ValueObject
{
    public function __construct(
        public readonly string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: {$value}");
        }
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
