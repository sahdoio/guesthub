<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Guest\Infrastructure\Persistence\Seeders\GuestSeeder;
use Modules\Reservation\Infrastructure\Persistence\Seeders\ReservationSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            GuestSeeder::class,
            ReservationSeeder::class,
        ]);
    }
}
