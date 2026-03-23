<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Repository;

use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ReservationId;
use Modules\Shared\Domain\PaginatedResult;

interface ReservationRepository
{
    public function save(Reservation $reservation): void;

    public function findByUuid(ReservationId $uuid): ?Reservation;

    public function findByUuidGlobal(ReservationId $uuid): ?Reservation;

    /** @return PaginatedResult<Reservation> */
    public function list(
        int $page = 1,
        int $perPage = 15,
        ?string $status = null,
        ?string $guestId = null,
        ?string $stayId = null,
    ): PaginatedResult;

    public function nextIdentity(): ReservationId;

    /** @return PaginatedResult<Reservation> */
    public function listByGuestId(
        string $guestId,
        int $page = 1,
        int $perPage = 15,
        ?string $status = null,
    ): PaginatedResult;

    public function count(): int;

    /** @return array<string, int> */
    public function countByStatus(): array;

    public function countTodayCheckIns(): int;

    public function countTodayCheckOuts(): int;
}
