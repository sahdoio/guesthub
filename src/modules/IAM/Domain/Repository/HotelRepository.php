<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Repository;

use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Hotel;
use Modules\IAM\Domain\HotelId;

interface HotelRepository
{
    public function save(Hotel $hotel): void;

    public function findByUuid(HotelId $uuid): ?Hotel;

    public function findBySlug(string $slug): ?Hotel;

    public function findByName(string $name): ?Hotel;

    /** @return list<Hotel> */
    public function findByAccountId(AccountId $accountId): array;

    /** @return list<Hotel> */
    public function findAll(): array;

    public function nextIdentity(): HotelId;

    public function resolveNumericId(HotelId $uuid): ?int;
}
