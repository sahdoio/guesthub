<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Repository\TypeRepository;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use Modules\IAM\Domain\Service\EmailUniquenessChecker;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\Service\TokenManager;
use Modules\IAM\Domain\Service\UserGateway;
use Modules\IAM\Infrastructure\Integration\UserGatewayAdapter;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentAccountRepository;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentActorRepository;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentEmailUniquenessChecker;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentActorTypeRepository;
use Modules\IAM\Infrastructure\Services\BcryptPasswordHasher;
use Modules\IAM\Infrastructure\Services\SanctumTokenManager;

final class IAMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ActorRepository::class, EloquentActorRepository::class);
        $this->app->bind(TypeRepository::class, EloquentActorTypeRepository::class);
        $this->app->bind(AccountRepository::class, EloquentAccountRepository::class);
        $this->app->bind(TokenManager::class, SanctumTokenManager::class);
        $this->app->bind(PasswordHasher::class, BcryptPasswordHasher::class);
        $this->app->bind(EmailUniquenessChecker::class, EloquentEmailUniquenessChecker::class);
        $this->app->bind(UserGateway::class, UserGatewayAdapter::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');

        Route::middleware('web')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
