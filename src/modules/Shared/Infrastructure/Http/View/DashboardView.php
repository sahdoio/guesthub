<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Guest\Application\Query\GetGuestStats;
use Modules\Guest\Application\Query\GetGuestStatsHandler;
use Modules\Reservation\Application\Query\GetReservationStats;
use Modules\Reservation\Application\Query\GetReservationStatsHandler;

final class DashboardView
{
    public function __construct(
        private GetGuestStatsHandler $guestStatsHandler,
        private GetReservationStatsHandler $reservationStatsHandler,
    ) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'guestStats' => $this->guestStatsHandler->handle(new GetGuestStats())->toArray(),
            'reservationStats' => $this->reservationStatsHandler->handle(new GetReservationStats())->toArray(),
        ]);
    }
}
