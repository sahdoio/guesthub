<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\ValueObject;

enum ActorType: string
{
    case GUEST = 'guest';
    case SYSTEM = 'system';
}
