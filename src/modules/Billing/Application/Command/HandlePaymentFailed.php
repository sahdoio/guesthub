<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

final readonly class HandlePaymentFailed
{
    public function __construct(
        public string $stripePaymentIntentId,
        public string $reason,
    ) {}
}
