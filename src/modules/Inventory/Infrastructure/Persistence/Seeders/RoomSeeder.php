<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\IAM\Infrastructure\Persistence\Seeders\AccountSeeder;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\Room;
use Modules\Inventory\Domain\ValueObject\RoomType;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

class RoomSeeder extends Seeder
{
    public function __construct(
        private readonly RoomRepository $repository,
        private readonly TenantContext $tenantContext,
    ) {}

    public function run(): void
    {
        $accountId = (int) AccountModel::where('uuid', AccountSeeder::$defaultAccountUuid)->value('id');
        $this->tenantContext->set($accountId);

        $rooms = [
            ['101', RoomType::SINGLE,  1, 1, 150.00, ['wifi', 'tv']],
            ['102', RoomType::SINGLE,  1, 1, 150.00, ['wifi', 'tv']],
            ['103', RoomType::DOUBLE,  1, 2, 250.00, ['wifi', 'tv', 'minibar']],
            ['104', RoomType::DOUBLE,  1, 2, 250.00, ['wifi', 'tv', 'minibar']],
            ['201', RoomType::SINGLE,  2, 1, 160.00, ['wifi', 'tv', 'safe']],
            ['202', RoomType::DOUBLE,  2, 2, 270.00, ['wifi', 'tv', 'minibar', 'safe']],
            ['203', RoomType::DOUBLE,  2, 2, 270.00, ['wifi', 'tv', 'minibar', 'safe']],
            ['204', RoomType::SUITE,   2, 4, 500.00, ['wifi', 'tv', 'minibar', 'safe', 'jacuzzi']],
            ['301', RoomType::DOUBLE,  3, 2, 280.00, ['wifi', 'tv', 'minibar', 'safe', 'balcony']],
            ['302', RoomType::SUITE,   3, 4, 550.00, ['wifi', 'tv', 'minibar', 'safe', 'jacuzzi', 'balcony']],
        ];

        foreach ($rooms as [$number, $type, $floor, $capacity, $price, $amenities]) {
            if ($this->repository->findByNumber($number) !== null) {
                continue;
            }

            $room = Room::create(
                uuid: $this->repository->nextIdentity(),
                number: $number,
                type: $type,
                floor: $floor,
                capacity: $capacity,
                pricePerNight: $price,
                amenities: $amenities,
                createdAt: new DateTimeImmutable,
            );
            $this->repository->save($room);
        }
    }
}
