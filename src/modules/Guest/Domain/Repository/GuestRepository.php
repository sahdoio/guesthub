<?php

declare(strict_types=1);

namespace Modules\Guest\Domain\Repository;

use Modules\Guest\Domain\Guest;
use Modules\Guest\Domain\GuestId;
use Modules\Shared\Domain\PaginatedResult;

interface GuestRepository
{
    public function save(Guest $guest): void;

    public function findByUuid(GuestId $uuid): ?Guest;

    public function findByEmail(string $email): ?Guest;

    public function findByDocument(string $document): ?Guest;

    public function list(int $page = 1, int $perPage = 15): PaginatedResult;

    public function remove(Guest $guest): void;

    public function nextIdentity(): GuestId;

    public function findByNumericId(int $id): ?Guest;

    public function resolveNumericId(GuestId $uuid): ?int;

    public function count(): int;

    public function countByLoyaltyTier(): array;
}
