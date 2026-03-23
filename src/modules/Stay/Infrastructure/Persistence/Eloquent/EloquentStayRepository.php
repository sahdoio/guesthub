<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Stay\Domain\Stay;
use Modules\Stay\Domain\StayId;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;
use Modules\Stay\Infrastructure\Persistence\StayReflector;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class EloquentStayRepository implements StayRepository
{
    public function __construct(
        private readonly StayModel $model,
        private readonly TenantContext $tenantContext,
    ) {}

    public function save(Stay $stay): void
    {
        $data = [
            'uuid' => $stay->uuid->value,
            'account_id' => $this->tenantContext->isSet() ? $this->tenantContext->id() : $this->resolveAccountNumericId($stay->accountId),
            'name' => $stay->name,
            'slug' => $stay->slug,
            'description' => $stay->description,
            'address' => $stay->address,
            'type' => $stay->type->value,
            'category' => $stay->category->value,
            'price_per_night' => $stay->pricePerNight,
            'capacity' => $stay->capacity,
            'contact_email' => $stay->contactEmail,
            'contact_phone' => $stay->contactPhone,
            'status' => $stay->status,
            'amenities' => $stay->amenities !== null ? json_encode($stay->amenities) : null,
            'cover_image_path' => $stay->coverImagePath,
            'created_at' => $stay->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $stay->updatedAt?->format('Y-m-d H:i:s'),
        ];

        $this->model->newQuery()
            ->withoutGlobalScopes()
            ->updateOrInsert(['uuid' => $data['uuid']], $data);
    }

    public function findByUuid(StayId $uuid): ?Stay
    {
        $record = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findBySlug(string $slug): ?Stay
    {
        $record = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByName(string $name): ?Stay
    {
        $record = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('name', $name)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    /** @return list<Stay> */
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

    /** @return list<Stay> */
    public function findAll(): array
    {
        return $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->get()
            ->map(fn ($record) => $this->toEntity($record))
            ->all();
    }

    public function nextIdentity(): StayId
    {
        return StayId::generate();
    }

    public function resolveNumericId(StayId $uuid): ?int
    {
        $id = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('uuid', $uuid->value)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    public function count(): int
    {
        return $this->model->newQuery()
            ->withoutGlobalScopes()
            ->count();
    }

    public function countByStatus(string $status): int
    {
        return $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('status', $status)
            ->count();
    }

    private function resolveAccountNumericId(AccountId $accountId): int
    {
        $id = AccountModel::query()->where('uuid', $accountId->value)->value('id');

        return (int) $id;
    }

    private function toEntity(object $record): Stay
    {
        $accountUuid = AccountModel::query()
            ->where('id', $record->account_id)
            ->value('uuid');

        return StayReflector::reconstruct(
            uuid: StayId::fromString($record->uuid),
            accountId: AccountId::fromString($accountUuid),
            name: $record->name,
            slug: $record->slug,
            description: $record->description,
            address: $record->address,
            type: StayType::from($record->type),
            category: StayCategory::from($record->category),
            pricePerNight: (float) ($record->price_per_night ?? 0),
            capacity: (int) ($record->capacity ?? 2),
            contactEmail: $record->contact_email,
            contactPhone: $record->contact_phone,
            status: $record->status ?? 'active',
            amenities: $record->amenities,
            coverImagePath: $record->cover_image_path,
            createdAt: new DateTimeImmutable($record->created_at),
            updatedAt: $record->updated_at ? new DateTimeImmutable($record->updated_at) : null,
        );
    }
}
