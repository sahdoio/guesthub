<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence;

final class TenantContext
{
    private ?string $accountUuid = null;

    public function set(string $accountUuid): void
    {
        $this->accountUuid = $accountUuid;
    }

    public function accountUuid(): string
    {
        if ($this->accountUuid !== null) {
            return $this->accountUuid;
        }

        throw new \RuntimeException('No tenant context available.');
    }

    public function isSet(): bool
    {
        return $this->accountUuid !== null;
    }
}
