<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;

/**
 * @template T of object
 */
abstract class BaseRepository
{
    abstract protected function tableName(): string;

    /** @return T */
    abstract protected function toEntity(object $record): object;

    /** @param T $entity */
    abstract protected function toRecord(object $entity): array;

    /** @return T|null */
    public function findByUuid(string $uuid): ?object
    {
        $record = DB::table($this->tableName())
            ->where('uuid', $uuid)
            ->first();

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

        $existing = DB::table($this->tableName())
            ->where('uuid', $data['uuid'])
            ->first();

        if ($existing) {
            DB::table($this->tableName())
                ->where('id', $existing->id)
                ->update($data);
        } else {
            DB::table($this->tableName())->insert($data);
        }
    }

    /** @param T $entity */
    public function remove(object $entity): void
    {
        DB::table($this->tableName())
            ->where('uuid', (string) $entity->id())
            ->delete();
    }
}
