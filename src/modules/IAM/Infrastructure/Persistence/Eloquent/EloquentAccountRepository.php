<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\IAM\Domain\Account;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Infrastructure\Persistence\AccountReflector;

final class EloquentAccountRepository implements AccountRepository
{
    public function __construct(
        private readonly AccountModel $model,
    ) {}

    public function save(Account $account): void
    {
        $data = [
            'uuid' => $account->uuid->value,
            'name' => $account->name,
            'slug' => $account->slug,
            'status' => $account->status,
            'created_at' => $account->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $account->updatedAt?->format('Y-m-d H:i:s'),
        ];

        $this->model->newQuery()->updateOrInsert(['uuid' => $data['uuid']], $data);
    }

    public function findByUuid(AccountId $uuid): ?Account
    {
        $record = $this->model->newQuery()->where('uuid', $uuid->value)->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function nextIdentity(): AccountId
    {
        return AccountId::generate();
    }

    public function findByNumericId(int $id): ?Account
    {
        $record = $this->model->newQuery()->where('id', $id)->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByName(string $name): ?Account
    {
        $record = $this->model->newQuery()->where('name', $name)->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findBySlug(string $slug): ?Account
    {
        $record = $this->model->newQuery()->where('slug', $slug)->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function resolveNumericId(AccountId $uuid): ?int
    {
        $id = $this->model->newQuery()->where('uuid', $uuid->value)->value('id');

        return $id !== null ? (int) $id : null;
    }

    /** @return list<Account> */
    public function findAll(): array
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->get()
            ->map(fn ($record) => $this->toEntity($record))
            ->all();
    }

    public function count(): int
    {
        return (int) $this->model->newQuery()->count();
    }

    private function toEntity(object $record): Account
    {
        return AccountReflector::reconstruct(
            uuid: AccountId::fromString($record->uuid),
            name: $record->name,
            slug: $record->slug ?? '',
            status: $record->status ?? 'active',
            createdAt: new DateTimeImmutable($record->created_at),
            updatedAt: $record->updated_at ? new DateTimeImmutable($record->updated_at) : null,
        );
    }
}
