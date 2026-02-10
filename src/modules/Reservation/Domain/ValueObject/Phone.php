<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\ValueObject;

use Modules\Shared\Domain\ValueObject;

final class Phone extends ValueObject
{
    public function __construct(
        public readonly string $value,
    ) {
        if (!preg_match('/^\+[1-9]\d{6,14}$/', $value)) {
            throw new \InvalidArgumentException(
                "Invalid phone number: {$value}. Expected E.164 format (e.g., +5511999999999)."
            );
        }
    }

    public static function fromString(string $phone): self
    {
        return new self($phone);
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
