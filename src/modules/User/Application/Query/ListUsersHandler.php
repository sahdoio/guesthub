<?php

declare(strict_types=1);

namespace Modules\User\Application\Query;

use Modules\User\Domain\Repository\UserRepository;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Domain\PaginatedResult;

final readonly class ListUsersHandler
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    /** @return PaginatedResult<\Modules\User\Domain\User> */
    public function handle(ListUsers $query, Pagination $pagination): PaginatedResult
    {
        return $this->repository->list($pagination->page, $pagination->perPage, $query->filters);
    }
}
