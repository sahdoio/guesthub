<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

use Modules\Shared\Domain\AggregateRoot;

abstract class EventDispatchingHandler
{
    public function __construct(
        protected EventDispatcher $dispatcher,
    ) {}

    protected function dispatchEvents(AggregateRoot $aggregate): void
    {
        foreach ($aggregate->pullDomainEvents() as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
}
