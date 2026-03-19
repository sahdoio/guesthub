<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Modules\IAM\Domain\Repository\TypeRepository;
use Modules\IAM\Domain\Type;
use Modules\IAM\Domain\TypeId;
use Modules\IAM\Domain\ValueObject\TypeName;

final class EloquentTypeRepository implements TypeRepository
{
    public function save(Type $type): void
    {
        TypeModel::query()->updateOrInsert(
            ['uuid' => $type->uuid->value],
            ['name' => $type->name->value],
        );
    }

    public function findById(TypeId $id): ?Type
    {
        $record = TypeModel::where('uuid', $id->value)->first();

        if (! $record) {
            return null;
        }

        return Type::create(
            uuid: TypeId::fromString($record->uuid),
            name: TypeName::from($record->name),
        );
    }

    public function findByName(TypeName $name): ?Type
    {
        $record = TypeModel::where('name', $name->value)->first();

        if (! $record) {
            return null;
        }

        return Type::create(
            uuid: TypeId::fromString($record->uuid),
            name: TypeName::from($record->name),
        );
    }

    public function nextIdentity(): TypeId
    {
        return TypeId::generate();
    }
}
