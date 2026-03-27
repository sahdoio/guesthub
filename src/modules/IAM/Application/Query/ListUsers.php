<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Query;

use Modules\Shared\Application\BaseData;

final readonly class ListUsers extends BaseData
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public array $filters = [],
    ) {}
}
