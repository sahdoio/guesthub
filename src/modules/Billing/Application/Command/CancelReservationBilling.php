<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class CancelReservationBilling extends BaseData
{
    public function __construct(
        public string $reservationId,
        public string $reason,
        public ?string $freeCancellationUntil = null,
    ) {}
}
