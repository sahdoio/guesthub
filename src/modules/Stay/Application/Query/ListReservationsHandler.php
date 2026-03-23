<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

use Illuminate\Support\Facades\Storage;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\Service\GuestGateway;
use Modules\Stay\Domain\StayId;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Domain\PaginatedResult;

final readonly class ListReservationsHandler
{
    public function __construct(
        private ReservationRepository $repository,
        private GuestGateway $guestGateway,
        private StayRepository $stayRepository,
    ) {}

    /** @return PaginatedResult<ReservationReadModel> */
    public function handle(ListReservations $query, Pagination $pagination): PaginatedResult
    {
        $result = $this->repository->list(
            page: $pagination->page,
            perPage: $pagination->perPage,
            status: $query->status,
            guestId: $query->guestId,
            stayId: $query->stayId,
        );

        $enrichedItems = array_map(
            fn (Reservation $reservation) => $this->enrichWithStay(
                $this->enrichWithGuest(
                    ReservationReadModel::fromReservation($reservation),
                ),
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

    private function enrichWithStay(ReservationReadModel $readModel): ReservationReadModel
    {
        $stayId = $readModel->stay['stay_id'] ?? null;

        if ($stayId === null || $stayId === '') {
            return $readModel;
        }

        $stay = $this->stayRepository->findByUuid(StayId::fromString($stayId));

        if ($stay === null) {
            return $readModel;
        }

        $coverImageUrl = null;
        if ($stay->coverImagePath !== null) {
            $disk = Storage::disk(config('filesystems.stays_disk', 'public'));
            $coverImageUrl = $disk->url($stay->coverImagePath);
        }

        return $readModel->withStay([
            'stay_id' => (string) $stay->uuid,
            'name' => $stay->name,
            'slug' => $stay->slug,
            'type' => $stay->type->value,
            'category' => $stay->category->value,
            'price_per_night' => $stay->pricePerNight,
            'address' => $stay->address,
            'cover_image_url' => $coverImageUrl,
        ]);
    }
}
