<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Messaging;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Application\Messaging\IntegrationEvent;

final class IntegrationEventPublisher
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    public function publish(IntegrationEvent $event): void
    {
        $eventClass = $event::class;

        Log::info("Publishing integration event: {$eventClass}", $event->toArray());

        $this->dispatcher->dispatch($event);
    }
}
