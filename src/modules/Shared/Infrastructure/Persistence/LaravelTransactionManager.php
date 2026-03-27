<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Application\TransactionManager;

final class LaravelTransactionManager implements TransactionManager
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
