<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

final readonly class AddSpecialRequest
{
    public function __construct(
        public string $reservationId,
        public string $requestType,
        public string $description,
    ) {}
}
