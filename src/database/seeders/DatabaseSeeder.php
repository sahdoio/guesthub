<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Guest\Infrastructure\Persistence\Seeders\GuestSeeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\AccountSeeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\ActorSeeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\RoleSeeder;
use Modules\Inventory\Infrastructure\Persistence\Seeders\RoomSeeder;
use Modules\Reservation\Infrastructure\Persistence\Seeders\ReservationSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AccountSeeder::class,
            GuestSeeder::class,
            ActorSeeder::class,
            RoomSeeder::class,
            ReservationSeeder::class,
        ]);
    }
}
