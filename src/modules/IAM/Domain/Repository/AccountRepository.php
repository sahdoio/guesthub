<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Repository;

use Modules\IAM\Domain\Account;
use Modules\IAM\Domain\AccountId;

interface AccountRepository
{
    public function save(Account $account): void;

    public function findByUuid(AccountId $uuid): ?Account;

    public function nextIdentity(): AccountId;

    /** @return list<Account> */
    public function findAll(): array;
}
