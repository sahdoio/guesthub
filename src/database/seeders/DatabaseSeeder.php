<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\AccountSeeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\ActorSeeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\HotelSeeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\TypeSeeder;
use Modules\User\Infrastructure\Persistence\Seeders\UserSeeder;
use Modules\Inventory\Infrastructure\Persistence\Seeders\RoomSeeder;
use Modules\Reservation\Infrastructure\Persistence\Seeders\ReservationSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            TypeSeeder::class,
            AccountSeeder::class,
            HotelSeeder::class,
            UserSeeder::class,
            ActorSeeder::class,
            RoomSeeder::class,
            ReservationSeeder::class,
        ]);
    }
}
