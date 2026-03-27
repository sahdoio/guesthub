<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Integration;

use Modules\Billing\Domain\Service\AccountGateway;
use Modules\Billing\Infrastructure\Exception\AccountNotFoundException;
use Modules\IAM\Infrastructure\Integration\AccountApi;

final class AccountGatewayAdapter implements AccountGateway
{
    public function __construct(
        private AccountApi $accountApi,
    ) {}

    public function resolveNumericId(string $accountUuid): int
    {
        $id = $this->accountApi->resolveNumericId($accountUuid);

        if ($id === null) {
            throw AccountNotFoundException::withUuid($accountUuid);
        }

        return $id;
    }
}
