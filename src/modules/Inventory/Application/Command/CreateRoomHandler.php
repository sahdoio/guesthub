<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command;

use DateTimeImmutable;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\Room;
use Modules\Inventory\Domain\RoomId;
use Modules\Inventory\Domain\ValueObject\RoomType;

final readonly class CreateRoomHandler
{
    public function __construct(
        private RoomRepository $repository,
    ) {}

    public function handle(CreateRoom $command): RoomId
    {
        $id = $this->repository->nextIdentity();

        $room = Room::create(
            uuid: $id,
            number: $command->number,
            type: RoomType::from($command->type),
            floor: $command->floor,
            capacity: $command->capacity,
            pricePerNight: $command->pricePerNight,
            amenities: $command->amenities,
            createdAt: new DateTimeImmutable,
        );

        $this->repository->save($room);

        return $id;
    }
}
