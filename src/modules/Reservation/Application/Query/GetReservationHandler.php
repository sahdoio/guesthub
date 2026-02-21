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
        $guestProfileId = $readModel->guest['guest_profile_id'] ?? null;

        if ($guestProfileId === null) {
            return $readModel;
        }

        $guestInfo = $this->guestGateway->findByUuid($guestProfileId);

        if ($guestInfo === null) {
            return $readModel;
        }

        return $readModel->withGuest([
            'guest_profile_id' => $guestInfo->guestProfileId,
            'full_name' => $guestInfo->fullName,
            'email' => $guestInfo->email,
            'phone' => $guestInfo->phone,
            'document' => $guestInfo->document,
            'is_vip' => $guestInfo->isVip,
        ]);
    }
}
