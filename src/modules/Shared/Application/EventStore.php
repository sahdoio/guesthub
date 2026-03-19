<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

interface EventStore
{
    public function append(StoredEvent $event): void;
}
