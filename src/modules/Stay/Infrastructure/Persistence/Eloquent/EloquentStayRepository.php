<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Shared\Domain\PaginatedResult;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\Stay;
use Modules\Stay\Domain\StayId;
use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;
use Modules\Stay\Infrastructure\Persistence\StayReflector;
use Ramsey\Uuid\Uuid;

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
    public function findAll(?int $limit = null): array
    {
        $query = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->orderBy('name');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
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

    /** @return PaginatedResult<Stay> */
    public function findActivePaginated(int $page = 1, int $perPage = 12, ?string $search = null, ?int $minCapacity = null): PaginatedResult
    {
        $query = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->orderBy('name');

        if ($search !== null && $search !== '') {
            $lower = mb_strtolower($search);
            $query->where(function ($qb) use ($lower) {
                $qb->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(address) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$lower}%"]);
            });
        }

        if ($minCapacity !== null && $minCapacity > 1) {
            $query->where('capacity', '>=', $minCapacity);
        }

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $items = collect($paginator->items())
            ->map(fn ($record) => $this->toEntity($record))
            ->all();

        return new PaginatedResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
        );
    }

    /** @return list<array{uuid: string, path: string, position: int}> */
    public function getImages(StayId $uuid): array
    {
        $stayId = $this->resolveNumericId($uuid);

        if ($stayId === null) {
            return [];
        }

        return StayImageModel::where('stay_id', $stayId)
            ->orderBy('position')
            ->get()
            ->map(fn (StayImageModel $img) => [
                'uuid' => $img->uuid,
                'path' => $img->path,
                'position' => $img->position,
            ])
            ->all();
    }

    public function addImage(StayId $uuid, string $path, int $position): void
    {
        $stayId = $this->resolveNumericId($uuid);

        if ($stayId === null) {
            return;
        }

        StayImageModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'stay_id' => $stayId,
            'path' => $path,
            'position' => $position,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    public function deleteImageByUuid(string $imageUuid): ?string
    {
        $image = StayImageModel::where('uuid', $imageUuid)->first();

        if ($image === null) {
            return null;
        }

        $path = $image->path;
        $image->delete();

        return $path;
    }

    public function countImages(StayId $uuid): int
    {
        $stayId = $this->resolveNumericId($uuid);

        return $stayId !== null ? StayImageModel::where('stay_id', $stayId)->count() : 0;
    }

    public function maxImagePosition(StayId $uuid): int
    {
        $stayId = $this->resolveNumericId($uuid);

        return $stayId !== null ? (int) (StayImageModel::where('stay_id', $stayId)->max('position') ?? 0) : 0;
    }

    public function countByType(string $type): int
    {
        return $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('type', $type)
            ->count();
    }

    public function countByCategory(string $category): int
    {
        return $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('category', $category)
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
