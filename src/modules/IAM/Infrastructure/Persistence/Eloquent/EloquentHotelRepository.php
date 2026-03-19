<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Hotel;
use Modules\IAM\Domain\HotelId;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\IAM\Infrastructure\Persistence\HotelReflector;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class EloquentHotelRepository implements HotelRepository
{
    public function __construct(
        private readonly HotelModel $model,
        private readonly TenantContext $tenantContext,
    ) {}

    public function save(Hotel $hotel): void
    {
        $data = [
            'uuid' => $hotel->uuid->value,
            'account_id' => $this->tenantContext->isSet() ? $this->tenantContext->id() : $this->resolveAccountNumericId($hotel->accountId),
            'name' => $hotel->name,
            'slug' => $hotel->slug,
            'description' => $hotel->description,
            'address' => $hotel->address,
            'contact_email' => $hotel->contactEmail,
            'contact_phone' => $hotel->contactPhone,
            'status' => $hotel->status,
            'created_at' => $hotel->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $hotel->updatedAt?->format('Y-m-d H:i:s'),
        ];

        $this->model->newQuery()
            ->withoutGlobalScopes()
            ->updateOrInsert(['uuid' => $data['uuid']], $data);
    }

    public function findByUuid(HotelId $uuid): ?Hotel
    {
        $record = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findBySlug(string $slug): ?Hotel
    {
        $record = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByName(string $name): ?Hotel
    {
        $record = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('name', $name)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    /** @return list<Hotel> */
    public function findByAccountId(AccountId $accountId): array
    {
        $numericId = $this->resolveAccountNumericId($accountId);

        return $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('account_id', $numericId)
            ->get()
            ->map(fn ($record) => $this->toEntity($record))
            ->all();
    }

    /** @return list<Hotel> */
    public function findAll(): array
    {
        return $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->get()
            ->map(fn ($record) => $this->toEntity($record))
            ->all();
    }

    public function nextIdentity(): HotelId
    {
        return HotelId::generate();
    }

    public function resolveNumericId(HotelId $uuid): ?int
    {
        $id = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('uuid', $uuid->value)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function resolveAccountNumericId(AccountId $accountId): int
    {
        $id = AccountModel::query()->where('uuid', $accountId->value)->value('id');

        return (int) $id;
    }

    private function toEntity(object $record): Hotel
    {
        $accountUuid = AccountModel::query()
            ->where('id', $record->account_id)
            ->value('uuid');

        return HotelReflector::reconstruct(
            uuid: HotelId::fromString($record->uuid),
            accountId: AccountId::fromString($accountUuid),
            name: $record->name,
            slug: $record->slug,
            description: $record->description,
            address: $record->address,
            contactEmail: $record->contact_email,
            contactPhone: $record->contact_phone,
            status: $record->status ?? 'active',
            createdAt: new DateTimeImmutable($record->created_at),
            updatedAt: $record->updated_at ? new DateTimeImmutable($record->updated_at) : null,
        );
    }
}
