<?php

declare(strict_types=1);

namespace Modules\Guest\Domain\Repository;

use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Shared\Domain\PaginatedResult;

interface GuestProfileRepository
{
    public function save(GuestProfile $profile): void;

    public function findByUuid(GuestProfileId $uuid): ?GuestProfile;

    public function findByEmail(string $email): ?GuestProfile;

    public function findByDocument(string $document): ?GuestProfile;

    /** @return PaginatedResult<GuestProfile> */
    public function paginate(int $page = 1, int $perPage = 15): PaginatedResult;

    public function remove(GuestProfile $profile): void;

    public function nextIdentity(): GuestProfileId;
}
