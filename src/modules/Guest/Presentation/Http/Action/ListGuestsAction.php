<?php

declare(strict_types=1);

namespace Modules\Guest\Presentation\Http\Action;

use Modules\Guest\Application\Query\ListGuests;
use Modules\Guest\Application\Query\ListGuestsHandler;
use Modules\Guest\Presentation\Http\Presenter\GuestPresenter;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListGuestsAction
{
    public function __construct(
        private ListGuestsHandler $handler,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 15)));

        $result = $this->handler->handle(new ListGuests, new Pagination($page, $perPage));

        return $this->responder->ok([
            'data' => array_map(
                fn ($guest) => GuestPresenter::fromDomain($guest),
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
