<?php

declare(strict_types=1);

namespace Modules\Shared\Domain;

use Ramsey\Uuid\Uuid;

abstract class Identity extends ValueObject
{
    public function __construct(
        public readonly string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid UUID: {$value}");
        }
    }

    public static function generate(): static
    {
        return new static(Uuid::uuid7()->toString());
    }

    public static function fromString(string $id): static
    {
        return new static($id);
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof static && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
