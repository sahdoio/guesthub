<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Action;

use Modules\Inventory\Application\Query\ListRooms;
use Modules\Inventory\Application\Query\ListRoomsHandler;
use Modules\Inventory\Presentation\Http\Presenter\RoomPresenter;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListRoomsAction
{
    public function __construct(
        private ListRoomsHandler $handler,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 15)));

        $result = $this->handler->handle(
            new ListRooms(
                status: $query['status'] ?? null,
                type: $query['type'] ?? null,
                floor: isset($query['floor']) ? (int) $query['floor'] : null,
            ),
            new Pagination($page, $perPage),
        );

        return $this->responder->ok([
            'data' => array_map(
                fn($room) => RoomPresenter::fromDomain($room),
                $result->items,
            ),
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
            ],
        ]);
    }
}
