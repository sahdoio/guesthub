<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence;

use Modules\IAM\Domain\Role;
use Modules\IAM\Domain\RoleId;
use Modules\IAM\Domain\ValueObject\RoleName;
use ReflectionClass;

final class RoleReflector
{
    public static function reconstruct(
        RoleId $uuid,
        RoleName $name,
    ): Role {
        $ref = new ReflectionClass(Role::class);
        $role = $ref->newInstanceWithoutConstructor();

        self::set($ref, $role, 'uuid', $uuid);
        self::set($ref, $role, 'name', $name);

        return $role;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
