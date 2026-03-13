<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\Guest\Domain\Guest;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Guest\Infrastructure\Persistence\GuestReflector;
use Modules\Shared\Domain\PaginatedResult;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class EloquentGuestRepository implements GuestRepository
{
    public function __construct(
        private readonly GuestModel $model,
        private readonly TenantContext $tenantContext,
    ) {}

    public function save(Guest $guest): void
    {
        $data = $this->toRecord($guest);

        $this->model->newQuery()
            ->updateOrInsert(['uuid' => $data['uuid']], $data);
    }

    public function findByUuid(GuestId $uuid): ?Guest
    {
        $record = $this->model->newQuery()
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByEmail(string $email): ?Guest
    {
        $record = $this->model->newQuery()
            ->where('email', $email)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByDocument(string $document): ?Guest
    {
        $record = $this->model->newQuery()
            ->where('document', $document)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function list(int $page = 1, int $perPage = 15): PaginatedResult
    {
        $paginator = $this->model->newQuery()
            ->orderByDesc('id')
            ->paginate(perPage: $perPage, page: $page);

        $items = collect($paginator->items())
            ->map(fn (object $record) => $this->toEntity($record))
            ->all();

        return new PaginatedResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
        );
    }

    public function remove(Guest $guest): void
    {
        $this->model->newQuery()
            ->where('uuid', $guest->uuid->value)
            ->delete();
    }

    public function nextIdentity(): GuestId
    {
        return GuestId::generate();
    }

    public function count(): int
    {
        return (int) $this->model->newQuery()->count();
    }

    public function countByLoyaltyTier(): array
    {
        return $this->model->newQuery()
            ->selectRaw('loyalty_tier, count(*) as total')
            ->groupBy('loyalty_tier')
            ->pluck('total', 'loyalty_tier')
            ->all();
    }

    private function toEntity(object $record): Guest
    {
        return GuestReflector::reconstruct(
            uuid: GuestId::fromString($record->uuid),
            fullName: $record->full_name,
            email: $record->email,
            phone: $record->phone,
            document: $record->document,
            loyaltyTier: LoyaltyTier::from($record->loyalty_tier),
            preferences: is_array($record->preferences) ? $record->preferences : json_decode($record->preferences ?? '[]', true),
            createdAt: new DateTimeImmutable($record->created_at),
            updatedAt: $record->updated_at ? new DateTimeImmutable($record->updated_at) : null,
        );
    }

    private function toRecord(Guest $guest): array
    {
        return [
            'uuid' => $guest->uuid->value,
            'account_id' => $this->tenantContext->id(),
            'full_name' => $guest->fullName,
            'email' => $guest->email,
            'phone' => $guest->phone,
            'document' => $guest->document,
            'loyalty_tier' => $guest->loyaltyTier->value,
            'preferences' => json_encode($guest->preferences),
            'created_at' => $guest->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $guest->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
