<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Guest\Infrastructure\Persistence\Seeders\GuestSeeder;
use Modules\Reservation\Infrastructure\Persistence\Seeders\ReservationSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            GuestSeeder::class,
            ReservationSeeder::class,
        ]);
    }
}
