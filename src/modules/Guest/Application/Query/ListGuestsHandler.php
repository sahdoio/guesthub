<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Query;

use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Domain\PaginatedResult;

final readonly class ListGuestsHandler
{
    public function __construct(
        private GuestRepository $repository,
    ) {}

    /** @return PaginatedResult<\Modules\Guest\Domain\Guest> */
    public function handle(ListGuests $query, Pagination $pagination): PaginatedResult
    {
        return $this->repository->list($pagination->page, $pagination->perPage);
    }
}
