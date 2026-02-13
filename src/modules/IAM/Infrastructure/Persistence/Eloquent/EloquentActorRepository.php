<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\ValueObject\ActorType;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\IAM\Infrastructure\Persistence\ActorReflector;

final class EloquentActorRepository implements ActorRepository
{
    public function __construct(
        private readonly ActorModel $model,
    ) {}

    public function save(Actor $actor): void
    {
        $data = $this->toRecord($actor);

        $this->model->newQuery()
            ->updateOrInsert(['uuid' => $data['uuid']], $data);
    }

    public function findByUuid(ActorId $uuid): ?Actor
    {
        $record = $this->model->newQuery()
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByEmail(string $email): ?Actor
    {
        $record = $this->model->newQuery()
            ->where('email', $email)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function nextIdentity(): ActorId
    {
        return ActorId::generate();
    }

    private function toRecord(Actor $actor): array
    {
        return [
            'uuid' => $actor->uuid->value,
            'type' => $actor->type->value,
            'name' => $actor->name,
            'email' => $actor->email,
            'password' => $actor->password->value,
            'profile_type' => $actor->profileType,
            'profile_id' => $actor->profileId,
            'created_at' => $actor->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $actor->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    private function toEntity(object $record): Actor
    {
        return ActorReflector::reconstruct(
            uuid: ActorId::fromString($record->uuid),
            type: ActorType::from($record->type),
            name: $record->name,
            email: $record->email,
            password: new HashedPassword($record->password),
            profileType: $record->profile_type,
            profileId: $record->profile_id !== null ? (string) $record->profile_id : null,
            createdAt: new DateTimeImmutable($record->created_at),
            updatedAt: $record->updated_at ? new DateTimeImmutable($record->updated_at) : null,
        );
    }
}
