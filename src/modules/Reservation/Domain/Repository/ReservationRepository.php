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

    /** @return PaginatedResult<Reservation> */
    public function list(
        int $page = 1,
        int $perPage = 15,
        ?string $status = null,
        ?string $roomType = null,
    ): PaginatedResult;

    public function nextIdentity(): ReservationId;
}
