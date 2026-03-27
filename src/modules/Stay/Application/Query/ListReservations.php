<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

use Modules\Shared\Application\BaseData;

final readonly class ListReservations extends BaseData
{
    public function __construct(
        public ?string $status = null,
        public ?string $guestId = null,
        public ?string $stayId = null,
    ) {}
}
