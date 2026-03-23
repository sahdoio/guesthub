<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Specification;

use DateTimeImmutable;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;

final class ReservationCreationSpecification
{
    private const int MAX_ADVANCE_DAYS_REGULAR = 60;

    private const int MAX_ADVANCE_DAYS_VIP = 90;

    private const int MIN_STAY_NIGHTS = 1;

    public function isSatisfiedBy(bool $isVip, ReservationPeriod $period): bool
    {
        $today = new DateTimeImmutable('today');

        if ($period->checkIn < $today) {
            return false;
        }

        if ($period->nights() < self::MIN_STAY_NIGHTS) {
            return false;
        }

        $maxAdvanceDays = $isVip ? self::MAX_ADVANCE_DAYS_VIP : self::MAX_ADVANCE_DAYS_REGULAR;
        $daysInAdvance = (int) $today->diff($period->checkIn)->days;

        if ($daysInAdvance > $maxAdvanceDays) {
            return false;
        }

        return true;
    }
}
