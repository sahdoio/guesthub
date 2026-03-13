<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command;

use Modules\Inventory\Domain\Exception\RoomNotFoundException;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\RoomId;
use Modules\Inventory\Domain\ValueObject\RoomStatus;

final readonly class ChangeRoomStatusHandler
{
    public function __construct(
        private RoomRepository $repository,
    ) {}

    public function handle(ChangeRoomStatus $command): void
    {
        $id = RoomId::fromString($command->roomId);
        $room = $this->repository->findByUuid($id)
            ?? throw RoomNotFoundException::withId($id);

        $targetStatus = RoomStatus::from($command->status);

        match ($targetStatus) {
            RoomStatus::AVAILABLE => $room->markAvailable(),
            RoomStatus::OCCUPIED => $room->occupy(),
            RoomStatus::MAINTENANCE => $room->markMaintenance(),
            RoomStatus::OUT_OF_ORDER => $room->markOutOfOrder(),
        };

        $this->repository->save($room);
    }
}
