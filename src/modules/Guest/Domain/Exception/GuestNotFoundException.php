<?php

declare(strict_types=1);

namespace Modules\Guest\Domain\Exception;

use Modules\Guest\Domain\GuestId;

final class GuestNotFoundException extends \DomainException
{
    public static function withId(GuestId $id): self
    {
        return new self("Guest with ID '{$id}' not found.");
    }
}
