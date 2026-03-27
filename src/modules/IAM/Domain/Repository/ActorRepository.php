<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Repository;

use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ValueObject\ActorId;

interface ActorRepository
{
    public function save(Actor $actor): void;

    public function findByUuid(ActorId $uuid): ?Actor;

    public function findByEmail(string $email): ?Actor;

    public function findByNumericId(int $id): ?Actor;

    /** @return list<array{id: int, uuid: string, name: string, email: string, type_names: list<string>}> */
    public function findActorsByAccountId(int $accountId): array;

    /** @return list<string> */
    public function resolveTypeNames(ActorId $uuid): array;

    public function nextIdentity(): ActorId;
}
