<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\ValueObject;

use InvalidArgumentException;
use Modules\Shared\Domain\ValueObject;

final class Money extends ValueObject
{
    public function __construct(
        public readonly int $amountInCents,
        public readonly string $currency = 'usd',
    ) {
        if (trim($currency) === '') {
            throw new InvalidArgumentException('Currency cannot be empty.');
        }
    }

    public static function zero(string $currency = 'usd'): self
    {
        return new self(0, $currency);
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountInCents - $other->amountInCents, $this->currency);
    }

    public function multiply(int $multiplier): self
    {
        return new self($this->amountInCents * $multiplier, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amountInCents === 0;
    }

    public function isPositive(): bool
    {
        return $this->amountInCents > 0;
    }

    public function format(): string
    {
        return sprintf(
            '%s %.2f',
            strtoupper($this->currency),
            $this->amountInCents / 100,
        );
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->amountInCents === $other->amountInCents
            && $this->currency === $other->currency;
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on different currencies: '{$this->currency}' and '{$other->currency}'.",
            );
        }
    }
}
