<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\ValueObject;

use DateTimeImmutable;
use Modules\Shared\Domain\ValueObject;

final class ReservationPeriod extends ValueObject
{
    public function __construct(
        public readonly DateTimeImmutable $checkIn,
        public readonly DateTimeImmutable $checkOut,
    ) {
        if ($checkOut <= $checkIn) {
            throw new \InvalidArgumentException('Check-out must be after check-in.');
        }

        $today = new DateTimeImmutable('today');
        if ($checkIn < $today) {
            throw new \InvalidArgumentException('Check-in cannot be in the past.');
        }

        if ($this->nights() > 365) {
            throw new \InvalidArgumentException('Maximum stay is 365 nights.');
        }
    }

    public function nights(): int
    {
        return (int) $this->checkIn->diff($this->checkOut)->days;
    }

    public function overlaps(ReservationPeriod $other): bool
    {
        return $this->checkIn < $other->checkOut && $other->checkIn < $this->checkOut;
    }

    public function contains(DateTimeImmutable $date): bool
    {
        return $date >= $this->checkIn && $date < $this->checkOut;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->checkIn == $other->checkIn
            && $this->checkOut == $other->checkOut;
    }
}
