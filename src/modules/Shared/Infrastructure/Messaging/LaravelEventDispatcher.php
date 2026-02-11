<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Messaging;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Shared\Application\EventDispatcher;

final class LaravelEventDispatcher implements EventDispatcher
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
