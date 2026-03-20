<?php

declare(strict_types=1);

namespace Modules\User\Application\Query;

final readonly class ListUsers
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public array $filters = [],
    ) {}
}
