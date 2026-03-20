<?php

declare(strict_types=1);

namespace Modules\User\Domain\Repository;

use Modules\User\Domain\User;
use Modules\User\Domain\UserId;
use Modules\Shared\Domain\PaginatedResult;

interface UserRepository
{
    public function save(User $user): void;

    public function findByUuid(UserId $uuid): ?User;

    public function findByEmail(string $email): ?User;

    public function findByDocument(string $document): ?User;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(int $page = 1, int $perPage = 15, array $filters = []): PaginatedResult;

    public function remove(User $user): void;

    public function nextIdentity(): UserId;

    public function findByNumericId(int $id): ?User;

    public function resolveNumericId(UserId $uuid): ?int;

    public function count(): int;

    public function countByLoyaltyTier(): array;
}
