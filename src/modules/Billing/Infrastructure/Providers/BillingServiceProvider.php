<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Billing\Domain\Event\InvoiceFullyPaid;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\Service\AccountGateway;
use Modules\Billing\Domain\Service\PaymentGateway;
use Modules\Billing\Domain\Service\ReservationGateway;
use Modules\Billing\Infrastructure\Integration\AccountGatewayAdapter;
use Modules\Billing\Infrastructure\Integration\ReservationGatewayAdapter;
use Modules\Billing\Infrastructure\Listeners\OnGuestCheckedOut;
use Modules\Billing\Infrastructure\Listeners\OnInvoiceFullyPaid;
use Modules\Billing\Infrastructure\Listeners\OnReservationCancelled;
use Modules\Billing\Infrastructure\Listeners\OnReservationCreated;
use Modules\Billing\Infrastructure\Persistence\Eloquent\EloquentInvoiceRepository;
use Modules\Billing\Infrastructure\Simulated\SimulatedPaymentGateway;
use Modules\Billing\Infrastructure\Stripe\StripePaymentGateway;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\TransactionManager;
use Modules\Shared\Infrastructure\Messaging\LaravelEventDispatcher;
use Modules\Shared\Infrastructure\Persistence\LaravelTransactionManager;
use Modules\Stay\Infrastructure\IntegrationEvent\GuestCheckedOutEvent;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationCancelledEvent;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationCreatedEvent;

final class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/billing.php', 'billing');

        $this->app->bind(InvoiceRepository::class, EloquentInvoiceRepository::class);
        $this->app->bind(AccountGateway::class, AccountGatewayAdapter::class);
        $this->app->bind(PaymentGateway::class, match (config('billing.gateway')) {
            'simulated' => SimulatedPaymentGateway::class,
            default => StripePaymentGateway::class,
        });
        $this->app->bind(ReservationGateway::class, ReservationGatewayAdapter::class);
        $this->app->bindIf(EventDispatcher::class, LaravelEventDispatcher::class);
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

        // Integration event listeners
        Event::listen(ReservationCreatedEvent::class, OnReservationCreated::class);
        Event::listen(InvoiceFullyPaid::class, OnInvoiceFullyPaid::class);
        Event::listen(GuestCheckedOutEvent::class, OnGuestCheckedOut::class);
        Event::listen(ReservationCancelledEvent::class, OnReservationCancelled::class);
    }
}
