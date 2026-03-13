<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exception;

use Modules\Inventory\Domain\ValueObject\RoomStatus;

final class InvalidRoomStateException extends \DomainException
{
    public static function forTransition(RoomStatus $from, RoomStatus $to): self
    {
        return new self("Cannot transition room from '{$from->value}' to '{$to->value}'.");
    }
}
