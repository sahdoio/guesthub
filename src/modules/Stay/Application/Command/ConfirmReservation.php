<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class ConfirmReservation extends BaseData
{
    public function __construct(
        public string $reservationId,
    ) {}
}
