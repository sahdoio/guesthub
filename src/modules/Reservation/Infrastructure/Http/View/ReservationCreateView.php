<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\User\Application\Query\ListUsers;
use Modules\User\Application\Query\ListUsersHandler;
use Modules\User\Presentation\Http\Presenter\UserPresenter;
use Modules\Shared\Application\Query\Pagination;

final class ReservationCreateView
{
    public function __construct(
        private ListUsersHandler $userHandler,
    ) {}

    public function __invoke(Request $request): Response
    {
        $users = $this->userHandler->handle(new ListUsers, new Pagination(1, 100));

        return Inertia::render('Reservations/Create', [
            'guests' => array_map(
                fn ($user) => UserPresenter::fromDomain($user),
                $users->items,
            ),
        ]);
    }
}
