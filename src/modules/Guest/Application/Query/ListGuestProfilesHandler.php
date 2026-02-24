<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Query;

use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Domain\PaginatedResult;

final class ListGuestProfilesHandler
{
    public function __construct(
        private readonly GuestProfileRepository $repository,
    ) {}

    /** @return PaginatedResult<\Modules\Guest\Domain\GuestProfile> */
    public function handle(ListGuestProfiles $query, Pagination $pagination): PaginatedResult
    {
        return $this->repository->list($pagination->page, $pagination->perPage);
    }
}
