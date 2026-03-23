<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Exception\UserNotFoundException;
use Modules\IAM\Domain\UserId;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Infrastructure\Http\Presenter\UserPresenter;
use Modules\Stay\Application\Query\ListReservations;
use Modules\Stay\Application\Query\ListReservationsHandler;
use Modules\Shared\Application\Query\Pagination;

final class UserShowView
{
    public function __construct(
        private UserRepository $repository,
        private ListReservationsHandler $listReservationsHandler,
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $user = $this->repository->findByUuid(UserId::fromString($id))
            ?? throw UserNotFoundException::withUuid($id);

        $reservations = $this->listReservationsHandler->handle(
            new ListReservations(guestId: $id),
            new Pagination(
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 10),
            ),
        );

        return Inertia::render('Guests/Show', [
            'guest' => UserPresenter::fromDomain($user),
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
