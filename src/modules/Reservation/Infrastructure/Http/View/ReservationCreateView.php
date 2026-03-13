<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Guest\Application\Query\ListGuests;
use Modules\Guest\Application\Query\ListGuestsHandler;
use Modules\Guest\Presentation\Http\Presenter\GuestPresenter;
use Modules\Shared\Application\Query\Pagination;

final class ReservationCreateView
{
    public function __construct(
        private ListGuestsHandler $guestHandler,
    ) {}

    public function __invoke(Request $request): Response
    {
        $guests = $this->guestHandler->handle(new ListGuests, new Pagination(1, 100));

        return Inertia::render('Reservations/Create', [
            'guests' => array_map(
                fn ($guest) => GuestPresenter::fromDomain($guest),
                $guests->items,
            ),
        ]);
    }
}
