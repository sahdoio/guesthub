<?php

declare(strict_types=1);

namespace Modules\Stay\Presentation\Http\Action;

use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Stay\Application\Query\ListReservations;
use Modules\Stay\Application\Query\ListReservationsHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListReservationsAction
{
    public function __construct(
        private ListReservationsHandler $handler,
        private AuthenticatedUserResolver $userResolver,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 15)));

        $guestId = null;
        if (! $this->userResolver->isOwnerOrSuperAdmin()) {
            $guestId = $this->userResolver->resolveUserUuid();
        }

        $result = $this->handler->handle(
            new ListReservations(
                status: $query['status'] ?? null,
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
