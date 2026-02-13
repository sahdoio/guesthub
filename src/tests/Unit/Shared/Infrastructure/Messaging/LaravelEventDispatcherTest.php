<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Infrastructure\Messaging;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Shared\Infrastructure\Messaging\LaravelEventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(LaravelEventDispatcher::class)]
final class LaravelEventDispatcherTest extends TestCase
{
    #[Test]
    public function itDelegatesToLaravelDispatcher(): void
    {
        $event = new stdClass();

        $laravelDispatcher = $this->createMock(Dispatcher::class);
        $laravelDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event);

        $dispatcher = new LaravelEventDispatcher($laravelDispatcher);
        $dispatcher->dispatch($event);
    }
}
