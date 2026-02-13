<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Infrastructure\Persistence\Eloquent\EloquentGuestProfileRepository;

final class GuestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GuestProfileRepository::class, EloquentGuestProfileRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
