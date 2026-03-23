<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\AccountSeeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\ActorSeeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\ActorTypeSeeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\UserSeeder;
use Modules\Billing\Infrastructure\Persistence\Seeders\InvoiceSeeder;
use Modules\Stay\Infrastructure\Persistence\Seeders\ReservationSeeder;
use Modules\Shared\Infrastructure\Persistence\Seeders\MassSeeder;
use Modules\Stay\Infrastructure\Persistence\Seeders\StayImageSeeder;
use Modules\Stay\Infrastructure\Persistence\Seeders\StaySeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            ActorTypeSeeder::class,
            AccountSeeder::class,
            StaySeeder::class,
            StayImageSeeder::class,
            UserSeeder::class,
            ActorSeeder::class,
            ReservationSeeder::class,
            InvoiceSeeder::class,
            MassSeeder::class,
        ]);
    }
}
