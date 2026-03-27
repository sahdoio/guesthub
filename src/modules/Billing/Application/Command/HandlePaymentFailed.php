<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class HandlePaymentFailed extends BaseData
{
    public function __construct(
        public string $stripePaymentIntentId,
        public string $reason,
    ) {}
}
