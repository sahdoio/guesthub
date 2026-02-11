<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Repository;

use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;

interface ActorRepository
{
    public function save(Actor $actor): void;

    public function findByUuid(ActorId $uuid): ?Actor;

    public function findByEmail(string $email): ?Actor;

    public function nextIdentity(): ActorId;
}
