<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\ValueObject;

enum RoleName: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case GUEST = 'guest';
}
