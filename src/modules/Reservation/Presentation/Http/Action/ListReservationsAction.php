<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use Modules\Guest\Infrastructure\Persistence\Eloquent\GuestModel;
use Modules\Reservation\Application\Query\ListReservations;
use Modules\Reservation\Application\Query\ListReservationsHandler;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListReservationsAction
{
    public function __construct(
        private ListReservationsHandler $handler,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 15)));

        $guestId = null;
        $user = auth()->user();
        if ($user) {
            $user->load('roles');
            $roleNames = $user->roles->pluck('name')->toArray();
            if (! in_array('admin', $roleNames, true) && ! in_array('superadmin', $roleNames, true)) {
                // Guest role: scope to own reservations
                if ($user->subject_type === 'guest' && $user->subject_id) {
                    $guestId = GuestModel::where('id', $user->subject_id)->value('uuid');
                }
            }
        }

        $result = $this->handler->handle(
            new ListReservations(
                status: $query['status'] ?? null,
                roomType: $query['room_type'] ?? null,
                guestId: $guestId,
            ),
            new Pagination($page, $perPage),
        );

        return $this->responder->ok([
            'data' => array_map(fn ($item) => $item->jsonSerialize(), $result->items),
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
            ],
        ]);
    }
}
