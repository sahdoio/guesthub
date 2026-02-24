<?php

declare(strict_types=1);

namespace Modules\Guest\Presentation\Http\Action;

use Modules\Guest\Application\Query\ListGuestProfiles;
use Modules\Guest\Application\Query\ListGuestProfilesHandler;
use Modules\Guest\Presentation\Http\Presenter\GuestProfilePresenter;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListGuestProfilesAction
{
    public function __construct(
        private ListGuestProfilesHandler $handler,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 15)));

        $result = $this->handler->handle(new ListGuestProfiles(), new Pagination($page, $perPage));

        return JsonResponder::ok([
            'data' => array_map(
                fn($profile) => GuestProfilePresenter::fromDomain($profile),
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
