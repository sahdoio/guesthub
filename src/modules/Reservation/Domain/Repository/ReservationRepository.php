<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Repository;

use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ReservationId;
use Modules\Shared\Domain\PaginatedResult;

interface ReservationRepository
{
    public function save(Reservation $reservation): void;

    public function findByUuid(ReservationId $uuid): ?Reservation;

    /** @return Reservation[] */
    public function findByGuestProfileId(string $guestProfileId): array;

    /** @return PaginatedResult<Reservation> */
    public function paginate(int $page = 1, int $perPage = 15): PaginatedResult;

    public function nextIdentity(): ReservationId;
}
