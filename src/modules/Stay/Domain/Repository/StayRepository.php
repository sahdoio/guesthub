<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Repository;

use Modules\IAM\Domain\AccountId;
use Modules\Stay\Domain\Stay;
use Modules\Stay\Domain\StayId;

interface StayRepository
{
    public function save(Stay $stay): void;

    public function findByUuid(StayId $uuid): ?Stay;

    public function findBySlug(string $slug): ?Stay;

    public function findByName(string $name): ?Stay;

    /** @return list<Stay> */
    public function findByAccountId(AccountId $accountId): array;

    /** @return list<Stay> */
    public function findAll(): array;

    public function nextIdentity(): StayId;

    public function resolveNumericId(StayId $uuid): ?int;

    public function count(): int;

    public function countByStatus(string $status): int;
}
