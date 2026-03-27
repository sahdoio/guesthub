<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

use Modules\Shared\Application\BaseData;

final readonly class ListStays extends BaseData
{
    public function __construct(
        public string $accountId,
    ) {}
}
