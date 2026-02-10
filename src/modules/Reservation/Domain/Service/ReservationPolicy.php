<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Service;

use Modules\Reservation\Domain\ValueObject\Guest;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;

final class ReservationPolicy
{
    private const int MAX_ADVANCE_DAYS_REGULAR = 60;
    private const int MAX_ADVANCE_DAYS_VIP = 90;
    private const int MIN_STAY_NIGHTS = 1;

    public function __construct(
        private readonly InventoryGateway $inventoryGateway,
    ) {}

    public function canCreateReservation(Guest $guest, ReservationPeriod $period, string $roomType): bool
    {
        if ($period->nights() < self::MIN_STAY_NIGHTS) {
            return false;
        }

        $maxAdvanceDays = $guest->isVip ? self::MAX_ADVANCE_DAYS_VIP : self::MAX_ADVANCE_DAYS_REGULAR;
        $today = new \DateTimeImmutable('today');
        $daysInAdvance = (int) $today->diff($period->checkIn)->days;

        if ($daysInAdvance > $maxAdvanceDays) {
            return false;
        }

        $availability = $this->inventoryGateway->checkAvailability($roomType, $period);

        return $availability->availableCount > 0;
    }
}
