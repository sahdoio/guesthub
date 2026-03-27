<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Event;

use Modules\Shared\Domain\DomainEvent;
use Modules\Stay\Domain\StayId;

final class StayCreated extends DomainEvent
{
    public function __construct(
        public readonly StayId $stayId,
        public readonly string $name,
    ) {
        parent::__construct();
    }
}
