<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence;

final class TenantContext
{
    private ?int $accountId = null;

    public function set(int $accountId): void
    {
        $this->accountId = $accountId;
    }

    public function id(): int
    {
        if ($this->accountId !== null) {
            return $this->accountId;
        }

        $user = auth()->user();

        if ($user !== null && ! empty($user->account_id)) {
            return (int) $user->account_id;
        }

        // Superadmin session-based tenant
        $sessionTenantId = session('tenant_account_id');
        if ($sessionTenantId !== null) {
            return (int) $sessionTenantId;
        }

        throw new \RuntimeException('No tenant context available.');
    }

    public function isSet(): bool
    {
        try {
            $this->id();

            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }
}
