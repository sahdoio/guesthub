<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Stay\Application\Query\ReservationReadModel;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\StayId;

final class PortalReservationsView
{
    public function __construct(
        private ReservationRepository $repository,
        private StayRepository $stayRepository,
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

        // Pre-fetch stays to avoid N+1
        $stayCache = [];
        $reservations = array_map(function (Reservation $r) use (&$stayCache) {
            $readModel = ReservationReadModel::fromReservation($r);

            if (! isset($stayCache[$r->stayId])) {
                $stay = $this->stayRepository->findByUuid(StayId::fromString($r->stayId));
                $stayCache[$r->stayId] = $stay ? [
                    'stay_id' => (string) $stay->uuid,
                    'name' => $stay->name,
                    'address' => $stay->address,
                ] : ['stay_id' => $r->stayId, 'name' => null, 'address' => null];
            }

            return $readModel->withStay($stayCache[$r->stayId])->jsonSerialize();
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
