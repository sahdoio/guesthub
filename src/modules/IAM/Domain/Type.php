<?php

declare(strict_types=1);

namespace Modules\IAM\Domain;

use Modules\IAM\Domain\ValueObject\TypeId;
use Modules\IAM\Domain\ValueObject\TypeName;
use Modules\Shared\Domain\Entity;
use Modules\Shared\Domain\Identity;

final class Type extends Entity
{
    private function __construct(
        public readonly TypeId $uuid,
        public readonly TypeName $name,
    ) {}

    public static function create(TypeId $uuid, TypeName $name): self
    {
        return new self(uuid: $uuid, name: $name);
    }

    public function id(): Identity
    {
        return $this->uuid;
    }
}
