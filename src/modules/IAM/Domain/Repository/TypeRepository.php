<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Repository;

use Modules\IAM\Domain\Type;
use Modules\IAM\Domain\TypeId;
use Modules\IAM\Domain\ValueObject\TypeName;

interface TypeRepository
{
    public function save(Type $type): void;

    public function findById(TypeId $id): ?Type;

    public function findByName(TypeName $name): ?Type;

    public function nextIdentity(): TypeId;
}
