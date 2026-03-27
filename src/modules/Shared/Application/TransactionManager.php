<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

interface TransactionManager
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
