<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

abstract readonly class BaseData implements JsonSerializable
{
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $data = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $value = $property->getValue($this);

            $data[$property->getName()] = $value instanceof self
                ? $value->toArray()
                : $value;
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
