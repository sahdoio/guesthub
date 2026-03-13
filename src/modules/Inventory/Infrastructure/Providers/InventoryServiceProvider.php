<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\EloquentRoomRepository;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RoomRepository::class, EloquentRoomRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');

        Route::middleware('web')
            ->group(__DIR__ . '/../Routes/web.php');
    }
}
