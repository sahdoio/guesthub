<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;

/**
 * @template T of object
 */
abstract class BaseRepository
{
    /** @return class-string<T> */
    abstract protected function entityClass(): string;

    abstract protected function tableName(): string;

    /** @return T */
    abstract protected function toEntity(object $record): object;

    /** @param T $entity */
    abstract protected function toRecord(object $entity): array;

    /** @return T|null */
    public function ofId(int|string $id): ?object
    {
        $record = DB::table($this->tableName())->find($id);

        return $record ? $this->toEntity($record) : null;
    }

    /** @return T[] */
    public function all(): array
    {
        return DB::table($this->tableName())
            ->get()
            ->map(fn(object $record) => $this->toEntity($record))
            ->all();
    }

    /** @param T $entity */
    public function save(object $entity): void
    {
        $data = $this->toRecord($entity);

        DB::table($this->tableName())->updateOrInsert(
            ['id' => $data['id']],
            $data,
        );
    }

    /** @param T $entity */
    public function remove(object $entity): void
    {
        DB::table($this->tableName())->delete($entity->id());
    }
}
