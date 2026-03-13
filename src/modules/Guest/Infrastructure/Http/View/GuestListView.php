<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Guest\Application\Query\ListGuestProfiles;
use Modules\Guest\Application\Query\ListGuestProfilesHandler;
use Modules\Guest\Presentation\Http\Presenter\GuestProfilePresenter;
use Modules\Shared\Application\Query\Pagination;

final class GuestListView
{
    public function __construct(
        private ListGuestProfilesHandler $handler,
    ) {}

    public function __invoke(Request $request): Response
    {
        $result = $this->handler->handle(
            new ListGuestProfiles(),
            new Pagination(
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 15),
            ),
        );

        return Inertia::render('Guests/Index', [
            'guests' => array_map(
                fn($profile) => GuestProfilePresenter::fromDomain($profile),
                $result->items,
            ),
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
            ],
            'filters' => [
                'loyalty_tier' => $request->query('loyalty_tier'),
            ],
        ]);
    }
}
