<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Application\Query\ListUsers;
use Modules\IAM\Application\Query\ListUsersHandler;
use Modules\IAM\Presentation\Http\Presenter\UserPresenter;
use Modules\Shared\Application\Query\Pagination;
use Modules\Stay\Domain\Repository\StayRepository;

final class ReservationCreateView
{
    public function __construct(
        private ListUsersHandler $userHandler,
        private StayRepository $stayRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $users = $this->userHandler->handle(new ListUsers, new Pagination(1, 100));

        $stayEntities = $this->stayRepository->findAll();

        $stays = array_map(fn ($stay) => [
            'stay_id' => (string) $stay->uuid,
            'name' => $stay->name,
            'type' => $stay->type->value,
            'category' => $stay->category->value,
        ], $stayEntities);

        return Inertia::render('Reservations/Create', [
            'guests' => array_map(
                fn ($user) => UserPresenter::fromDomain($user),
                $users->items,
            ),
            'stays' => $stays,
        ]);
    }
}
