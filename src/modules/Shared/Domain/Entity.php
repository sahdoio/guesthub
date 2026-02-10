<?php

declare(strict_types=1);

namespace Modules\Shared\Domain;

abstract class Entity
{
    abstract public function id(): Identity;

    public function equals(Entity $other): bool
    {
        return static::class === $other::class
            && $this->id()->equals($other->id());
    }
}
