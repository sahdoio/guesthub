<?php

declare(strict_types=1);

namespace Modules\Shared\Domain;

abstract class ValueObject
{
    abstract public function equals(ValueObject $other): bool;
}
