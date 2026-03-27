<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Query;

use Modules\Shared\Application\BaseData;

final readonly class Pagination extends BaseData
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
    ) {}
}
