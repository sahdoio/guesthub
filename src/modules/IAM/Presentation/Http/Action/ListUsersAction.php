<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Action;

use Modules\IAM\Application\Query\ListUsers;
use Modules\IAM\Application\Query\ListUsersHandler;
use Modules\IAM\Presentation\Http\Presenter\UserPresenter;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListUsersAction
{
    public function __construct(
        private ListUsersHandler $handler,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 15)));

        $result = $this->handler->handle(new ListUsers, new Pagination($page, $perPage));

        return $this->responder->ok([
            'data' => array_map(
                fn ($user) => UserPresenter::fromDomain($user),
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
