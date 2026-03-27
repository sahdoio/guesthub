<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\IAM\Domain\Event\UserCreated;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Repository\TypeRepository;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\Service\EmailUniquenessChecker;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\Service\TokenManager;
use Modules\IAM\Domain\Service\UserEmailUniquenessChecker;
use Modules\IAM\Infrastructure\Listeners\OnUserCreated;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentAccountRepository;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentActorRepository;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentActorTypeRepository;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentEmailUniquenessChecker;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentUserEmailUniquenessChecker;
use Modules\IAM\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use Modules\IAM\Infrastructure\Services\BcryptPasswordHasher;
use Modules\IAM\Infrastructure\Services\SanctumTokenManager;
use Modules\Shared\Application\TransactionManager;
use Modules\Shared\Infrastructure\Persistence\LaravelTransactionManager;

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
        $this->app->bind(UserEmailUniquenessChecker::class, EloquentUserEmailUniquenessChecker::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bindIf(TransactionManager::class, LaravelTransactionManager::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');

        Route::middleware('web')
            ->group(__DIR__.'/../Routes/web.php');

        Event::listen(UserCreated::class, OnUserCreated::class);
    }
}
