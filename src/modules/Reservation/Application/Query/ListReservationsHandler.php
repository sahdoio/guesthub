<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Query;

use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Service\GuestGateway;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Domain\PaginatedResult;

final readonly class ListReservationsHandler
{
    public function __construct(
        private ReservationRepository $repository,
        private GuestGateway $guestGateway,
    ) {}

    /** @return PaginatedResult<ReservationReadModel> */
    public function handle(ListReservations $query, Pagination $pagination): PaginatedResult
    {
        $result = $this->repository->list(
            page: $pagination->page,
            perPage: $pagination->perPage,
            status: $query->status,
            roomType: $query->roomType,
        );

        $enrichedItems = array_map(
            fn(Reservation $reservation) => $this->enrichWithGuest(
                ReservationReadModel::fromReservation($reservation),
            ),
            $result->items,
        );

        return new PaginatedResult(
            items: $enrichedItems,
            total: $result->total,
            perPage: $result->perPage,
            currentPage: $result->currentPage,
            lastPage: $result->lastPage,
        );
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
