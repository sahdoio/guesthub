<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Application\EventStore;
use Modules\Shared\Domain\DomainEvent;
use Modules\Shared\Infrastructure\Messaging\EventSerializer;
use Modules\Shared\Infrastructure\Messaging\EventStoreRecorder;
use Modules\Shared\Infrastructure\Messaging\IntegrationEvent;
use Modules\Shared\Infrastructure\Persistence\Eloquent\EloquentEventStore;

final class EventStoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventStore::class, EloquentEventStore::class);
        $this->app->singleton(EventSerializer::class);
        $this->app->singleton(EventStoreRecorder::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Migrations');

        $recorder = $this->app->make(EventStoreRecorder::class);

        Event::listen('*', function (string $eventName, array $data) use ($recorder): void {
            $event = $data[0] ?? null;

            if ($event instanceof DomainEvent || $event instanceof IntegrationEvent) {
                $recorder->record($event);
            }
        });
    }
}
