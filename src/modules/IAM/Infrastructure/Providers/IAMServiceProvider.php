<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\Service\TokenManager;
use Modules\IAM\Infrastructure\Services\BcryptPasswordHasher;
use Modules\IAM\Infrastructure\Services\SanctumTokenManager;
use Modules\IAM\Infrastructure\Persistence\QueryBuilderActorRepository;

final class IAMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ActorRepository::class, QueryBuilderActorRepository::class);
        $this->app->bind(TokenManager::class, SanctumTokenManager::class);
        $this->app->bind(PasswordHasher::class, BcryptPasswordHasher::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
