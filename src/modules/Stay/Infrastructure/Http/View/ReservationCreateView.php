<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Application\Query\ListUsers;
use Modules\IAM\Application\Query\ListUsersHandler;
use Modules\IAM\Infrastructure\Http\Presenter\UserPresenter;
use Modules\Shared\Application\Query\Pagination;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;

final class ReservationCreateView
{
    public function __construct(
        private ListUsersHandler $userHandler,
    ) {}

    public function __invoke(Request $request): Response
    {
        $users = $this->userHandler->handle(new ListUsers, new Pagination(1, 100));

        $stays = StayModel::query()
            ->where('status', 'active')
            ->get(['uuid', 'name', 'type', 'category'])
            ->map(fn (StayModel $stay) => [
                'stay_id' => $stay->uuid,
                'name' => $stay->name,
                'type' => $stay->type,
                'category' => $stay->category,
            ])
            ->all();

        return Inertia::render('Reservations/Create', [
            'guests' => array_map(
                fn ($user) => UserPresenter::fromDomain($user),
                $users->items,
            ),
            'stays' => $stays,
        ]);
    }
}
