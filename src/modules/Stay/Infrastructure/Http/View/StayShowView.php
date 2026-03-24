<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Shared\Application\Query\Pagination;
use Modules\Stay\Application\Query\ListReservations;
use Modules\Stay\Application\Query\ListReservationsHandler;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Presentation\Http\Presenter\StayPresenter;

final class StayShowView
{
    public function __construct(
        private StayRepository $stayRepository,
        private ListReservationsHandler $listReservationsHandler,
        private StayPresenter $stayPresenter,
    ) {}

    public function __invoke(Request $request, string $slug): Response
    {
        $stay = $this->stayRepository->findBySlug($slug);

        abort_if($stay === null, 404);

        $reservations = $this->listReservationsHandler->handle(
            new ListReservations(stayId: (string) $stay->uuid),
            new Pagination(
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 10),
            ),
        );

        return Inertia::render('Stays/Show', [
            'stay' => $this->stayPresenter->toArray($stay),
            'reservations' => $reservations->items,
            'reservationsMeta' => [
                'current_page' => $reservations->currentPage,
                'last_page' => $reservations->lastPage,
                'per_page' => $reservations->perPage,
                'total' => $reservations->total,
            ],
        ]);
    }
}
