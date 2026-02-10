<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Repository;

use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\ValueObject\Email;

interface ReservationRepository
{
    public function save(Reservation $reservation): void;

    public function findById(ReservationId $id): ?Reservation;

    /** @return Reservation[] */
    public function findByGuestEmail(Email $email): array;

    public function nextIdentity(): ReservationId;
}
