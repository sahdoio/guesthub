<?php

declare(strict_types=1);

namespace Tests\Concerns;

use DateTimeImmutable;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\Room;
use Modules\Inventory\Domain\ValueObject\RoomType;

trait SeedsRooms
{
    protected function seedRooms(): void
    {
        $repository = $this->app->make(RoomRepository::class);

        foreach (['SINGLE', 'DOUBLE', 'SUITE'] as $i => $type) {
            $room = Room::create(
                uuid: $repository->nextIdentity(),
                number: (string) (101 + $i),
                type: RoomType::from($type),
                floor: 1,
                capacity: $type === 'SUITE' ? 4 : ($type === 'DOUBLE' ? 2 : 1),
                pricePerNight: 250.00,
                amenities: ['wifi'],
                createdAt: new DateTimeImmutable,
            );
            $repository->save($room);
        }
    }
}
