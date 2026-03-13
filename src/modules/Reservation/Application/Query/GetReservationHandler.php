<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Query;

use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Service\GuestGateway;

final readonly class GetReservationHandler
{
    public function __construct(
        private ReservationRepository $repository,
        private GuestGateway $guestGateway,
    ) {}

    public function handle(GetReservation $query): ReservationReadModel
    {
        $id = ReservationId::fromString($query->reservationId);

        $reservation = $this->repository->findByUuid($id)
            ?? throw ReservationNotFoundException::withId($id);

        $readModel = ReservationReadModel::fromReservation($reservation);

        return $this->enrichWithGuest($readModel);
    }

    private function enrichWithGuest(ReservationReadModel $readModel): ReservationReadModel
    {
        $guestId = $readModel->guest['guest_id'] ?? null;

        if ($guestId === null) {
            return $readModel;
        }

        $guestInfo = $this->guestGateway->findByUuid($guestId);

        if ($guestInfo === null) {
            return $readModel;
        }

        return $readModel->withGuest([
            'guest_id' => $guestInfo->guestId,
            'full_name' => $guestInfo->fullName,
            'email' => $guestInfo->email,
            'phone' => $guestInfo->phone,
            'document' => $guestInfo->document,
            'is_vip' => $guestInfo->isVip,
        ]);
    }
}
