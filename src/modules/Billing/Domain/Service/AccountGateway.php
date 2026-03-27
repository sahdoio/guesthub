<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Service;

interface AccountGateway
{
    public function resolveNumericId(string $accountUuid): int;
}
