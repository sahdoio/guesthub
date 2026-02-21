<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Specification;

use DateTimeImmutable;
use Modules\Reservation\Domain\Service\InventoryGateway;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;

final class ReservationCreationSpecification
{
    private const int MAX_ADVANCE_DAYS_REGULAR = 60;
    private const int MAX_ADVANCE_DAYS_VIP = 90;
    private const int MIN_STAY_NIGHTS = 1;

    public function __construct(
        private readonly InventoryGateway $inventoryGateway,
    ) {}

    public function isSatisfiedBy(bool $isVip, ReservationPeriod $period, string $roomType): bool
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

        $availability = $this->inventoryGateway->checkAvailability($roomType, $period);

        return $availability->availableCount > 0;
    }
}
