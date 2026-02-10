<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Query;

final readonly class ListGuestProfiles
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
    ) {}
}
