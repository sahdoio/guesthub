<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\HotelId;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\Reservation\Application\Query\ReservationReadModel;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Reservation;

final class PortalReservationsView
{
    public function __construct(
        private ReservationRepository $repository,
        private HotelRepository $hotelRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $guestUuid = $request->attributes->get('guest_uuid');

        $result = $this->repository->listByGuestId(
            guestId: $guestUuid,
            page: (int) $request->query('page', 1),
            perPage: (int) $request->query('per_page', 15),
            status: $request->query('status'),
        );

        // Pre-fetch hotels to avoid N+1
        $hotelCache = [];
        $reservations = array_map(function (Reservation $r) use (&$hotelCache) {
            $readModel = ReservationReadModel::fromReservation($r);

            if (! isset($hotelCache[$r->hotelId])) {
                $hotel = $this->hotelRepository->findByUuid(HotelId::fromString($r->hotelId));
                $hotelCache[$r->hotelId] = $hotel ? [
                    'hotel_id' => (string) $hotel->uuid,
                    'name' => $hotel->name,
                    'address' => $hotel->address,
                ] : ['hotel_id' => $r->hotelId, 'name' => null, 'address' => null];
            }

            return $readModel->withHotel($hotelCache[$r->hotelId])->jsonSerialize();
        }, $result->items);

        return Inertia::render('Portal/Reservations/Index', [
            'reservations' => $reservations,
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
            ],
            'filters' => [
                'status' => $request->query('status'),
            ],
        ]);
    }
}
