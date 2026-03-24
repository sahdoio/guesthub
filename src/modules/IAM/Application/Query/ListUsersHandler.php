<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Query;

use Modules\IAM\Domain\Repository\UserRepository;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Domain\PaginatedResult;

final readonly class ListUsersHandler
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    /** @return PaginatedResult<\Modules\IAM\Domain\User> */
    public function handle(ListUsers $query, Pagination $pagination): PaginatedResult
    {
        return $this->repository->list($pagination->page, $pagination->perPage, $query->filters);
    }
}
