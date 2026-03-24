<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

final readonly class ListStays
{
    public function __construct(
        public string $accountId,
    ) {}
}
