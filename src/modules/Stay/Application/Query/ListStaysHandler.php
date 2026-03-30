<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\Stay;

final readonly class ListStaysHandler
{
    public function __construct(
        private StayRepository $repository,
    ) {}

    /**
     * @return list<Stay>
     */
    public function handle(ListStays $query): array
    {
        return $this->repository->findByAccountUuid($query->accountId);
    }
}
