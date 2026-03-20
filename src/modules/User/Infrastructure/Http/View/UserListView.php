<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\User\Application\Query\ListUsers;
use Modules\User\Application\Query\ListUsersHandler;
use Modules\User\Presentation\Http\Presenter\UserPresenter;
use Modules\Shared\Application\Query\Pagination;

final class UserListView
{
    public function __construct(
        private ListUsersHandler $handler,
    ) {}

    public function __invoke(Request $request): Response
    {
        $filters = [
            'search' => $request->query('search'),
            'loyalty_tier' => $request->query('loyalty_tier'),
        ];

        $result = $this->handler->handle(
            new ListUsers(filters: array_filter($filters)),
            new Pagination(
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 15),
            ),
        );

        return Inertia::render('Guests/Index', [
            'guests' => array_map(
                fn ($user) => UserPresenter::fromDomain($user),
                $result->items,
            ),
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
            ],
            'filters' => [
                'search' => $request->query('search', ''),
                'loyalty_tier' => $request->query('loyalty_tier', ''),
            ],
        ]);
    }
}
