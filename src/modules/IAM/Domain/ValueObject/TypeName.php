<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\ValueObject;

enum TypeName: string
{
    case SUPERADMIN = 'superadmin';
    case OWNER = 'owner';
    case GUEST = 'guest';
}
