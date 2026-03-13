<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exception;

use Modules\Inventory\Domain\RoomId;

final class RoomNotFoundException extends \DomainException
{
    public static function withId(RoomId $id): self
    {
        return new self("Room with ID '{$id}' not found.");
    }

    public static function withNumber(string $number): self
    {
        return new self("Room with number '{$number}' not found.");
    }
}
