<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Exception;

use RuntimeException;

final class StayNotFoundException extends RuntimeException
{
    public static function withUuid(string $uuid): self
    {
        return new self("Stay with UUID {$uuid} not found.");
    }

    public static function withSlug(string $slug): self
    {
        return new self("Stay with slug {$slug} not found.");
    }
}
