<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Repository;

use Modules\Inventory\Domain\Room;
use Modules\Inventory\Domain\RoomId;
use Modules\Shared\Domain\PaginatedResult;

interface RoomRepository
{
    public function setHotelId(int $hotelId): void;

    public function save(Room $room): void;

    public function findByUuid(RoomId $uuid): ?Room;

    public function findByNumber(string $number): ?Room;

    /** @return PaginatedResult<Room> */
    public function list(
        int $page = 1,
        int $perPage = 15,
        ?string $status = null,
        ?string $type = null,
        ?int $floor = null,
        ?int $hotelId = null,
    ): PaginatedResult;

    public function remove(Room $room): void;

    public function nextIdentity(): RoomId;

    public function count(): int;

    /** @return array<string, int> */
    public function countByStatus(): array;

    /** @return array<string, int> */
    public function countByType(): array;

    public function countAvailableByType(string $type): int;

    /** @return array<int, array{type: string, available: int, min_price: float}> */
    public function getAvailableRoomTypes(): array;
}
