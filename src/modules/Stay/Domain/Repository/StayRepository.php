<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Repository;

use Modules\Shared\Domain\PaginatedResult;
use Modules\Stay\Domain\Stay;
use Modules\Stay\Domain\StayId;

interface StayRepository
{
    public function save(Stay $stay): void;

    public function findByUuid(StayId $uuid): ?Stay;

    public function findBySlug(string $slug): ?Stay;

    public function findByName(string $name): ?Stay;

    /** @return list<Stay> */
    public function findByAccountUuid(string $accountUuid): array;

    /** @return list<Stay> */
    public function findAll(?int $limit = null): array;

    public function nextIdentity(): StayId;

    public function resolveNumericId(StayId $uuid): ?int;

    public function count(): int;

    public function countByStatus(string $status): int;

    /** @return PaginatedResult<Stay> */
    public function findActivePaginated(int $page = 1, int $perPage = 12, ?string $search = null, ?int $minCapacity = null): PaginatedResult;

    /** @return list<array{uuid: string, path: string, position: int}> */
    public function getImages(StayId $uuid): array;

    public function addImage(StayId $uuid, string $path, int $position): void;

    /** Returns the deleted image's file path, or null if not found */
    public function deleteImageByUuid(string $imageUuid): ?string;

    public function countImages(StayId $uuid): int;

    public function maxImagePosition(StayId $uuid): int;

    public function countByType(string $type): int;

    public function countByCategory(string $category): int;
}
