<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Presentation\Http\Presenter\GuestPresenter;
use Modules\Reservation\Application\Query\ListReservations;
use Modules\Reservation\Application\Query\ListReservationsHandler;
use Modules\Shared\Application\Query\Pagination;

final class PortalDashboardView
{
    public function __construct(
        private GuestRepository $guestRepository,
        private ListReservationsHandler $reservationsHandler,
    ) {}

    public function __invoke(Request $request): Response
    {
        $guestUuid = $request->attributes->get('guest_uuid');

        $guest = $guestUuid
            ? $this->guestRepository->findByUuid(GuestId::fromString($guestUuid))
            : null;

        $reservations = $this->reservationsHandler->handle(
            new ListReservations(guestId: $guestUuid),
            new Pagination(page: 1, perPage: 5),
        );

        return Inertia::render('Portal/Dashboard', [
            'guest' => $guest ? GuestPresenter::fromDomain($guest) : null,
            'reservations' => $reservations->items,
            'reservationsMeta' => [
                'total' => $reservations->total,
            ],
        ]);
    }
}
