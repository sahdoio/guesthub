<?php

declare(strict_types=1);

namespace Modules\Guest\Domain\Exception;

use Modules\Guest\Domain\GuestProfileId;

final class GuestProfileNotFoundException extends \DomainException
{
    public static function withId(GuestProfileId $id): self
    {
        return new self("Guest profile with ID '{$id}' not found.");
    }
}
