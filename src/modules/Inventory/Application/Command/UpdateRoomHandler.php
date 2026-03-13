<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command;

use Modules\Inventory\Domain\Exception\RoomNotFoundException;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\RoomId;

final readonly class UpdateRoomHandler
{
    public function __construct(
        private RoomRepository $repository,
    ) {}

    public function handle(UpdateRoom $command): void
    {
        $id = RoomId::fromString($command->roomId);
        $room = $this->repository->findByUuid($id)
            ?? throw RoomNotFoundException::withId($id);

        $room->updateDetails(
            pricePerNight: $command->pricePerNight,
            amenities: $command->amenities,
        );

        $this->repository->save($room);
    }
}
