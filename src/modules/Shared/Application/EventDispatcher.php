<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

interface EventDispatcher
{
    public function dispatch(object $event): void;
}
