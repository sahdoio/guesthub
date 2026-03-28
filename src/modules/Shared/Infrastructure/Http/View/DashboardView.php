<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Application\Query\GetBillingStats;
use Modules\Billing\Application\Query\GetBillingStatsHandler;
use Modules\IAM\Application\Query\GetUserStats;
use Modules\IAM\Application\Query\GetUserStatsHandler;
use Modules\Shared\Application\Query\Pagination;
use Modules\Stay\Application\Query\GetReservationStats;
use Modules\Stay\Application\Query\GetReservationStatsHandler;
use Modules\Stay\Application\Query\GetStayStats;
use Modules\Stay\Application\Query\GetStayStatsHandler;
use Modules\Stay\Application\Query\ListReservations;
use Modules\Stay\Application\Query\ListReservationsHandler;

final readonly class DashboardView
{
    public function __construct(
        private GetUserStatsHandler $userStatsHandler,
        private GetReservationStatsHandler $reservationStatsHandler,
        private GetStayStatsHandler $stayStatsHandler,
        private GetBillingStatsHandler $billingStatsHandler,
        private ListReservationsHandler $listReservationsHandler,
    ) {}

    public function __invoke(Request $request): Response
    {
        $pendingReservations = $this->listReservationsHandler->handle(
            new ListReservations(status: 'pending'),
            new Pagination(page: 1, perPage: 10),
        );

        $upcomingReservations = $this->listReservationsHandler->handle(
            new ListReservations(upcoming: true),
            new Pagination(page: 1, perPage: 10),
        );

        return Inertia::render('Dashboard', [
            'guestStats' => $this->userStatsHandler->handle(new GetUserStats)->toArray(),
            'reservationStats' => $this->reservationStatsHandler->handle(new GetReservationStats)->toArray(),
            'stayStats' => $this->stayStatsHandler->handle(new GetStayStats)->toArray(),
            'billingStats' => $this->billingStatsHandler->handle(new GetBillingStats)->toArray(),
            'pendingReservations' => $pendingReservations->items,
            'upcomingReservations' => $upcomingReservations->items,
        ]);
    }
}
