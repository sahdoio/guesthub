<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Guest\Application\Query\ListGuestProfiles;
use Modules\Guest\Application\Query\ListGuestProfilesHandler;
use Modules\Guest\Presentation\Http\Presenter\GuestProfilePresenter;
use Modules\Shared\Application\Query\Pagination;

final class ReservationCreateView
{
    public function __construct(
        private ListGuestProfilesHandler $guestHandler,
    ) {}

    public function __invoke(Request $request): Response
    {
        $guests = $this->guestHandler->handle(new ListGuestProfiles(), new Pagination(1, 100));

        return Inertia::render('Reservations/Create', [
            'guests' => array_map(
                fn ($guest) => GuestProfilePresenter::fromDomain($guest),
                $guests->items,
            ),
        ]);
    }
}
