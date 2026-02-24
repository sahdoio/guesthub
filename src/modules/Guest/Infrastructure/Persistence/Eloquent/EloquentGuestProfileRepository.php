<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Guest\Infrastructure\Persistence\GuestProfileReflector;
use Modules\Shared\Domain\PaginatedResult;

final class EloquentGuestProfileRepository implements GuestProfileRepository
{
    public function __construct(
        private readonly GuestProfileModel $model,
    ) {}

    public function save(GuestProfile $profile): void
    {
        $data = $this->toRecord($profile);

        $this->model->newQuery()
            ->updateOrInsert(['uuid' => $data['uuid']], $data);
    }

    public function findByUuid(GuestProfileId $uuid): ?GuestProfile
    {
        $record = $this->model->newQuery()
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByEmail(string $email): ?GuestProfile
    {
        $record = $this->model->newQuery()
            ->where('email', $email)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByDocument(string $document): ?GuestProfile
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
            ->map(fn(object $record) => $this->toEntity($record))
            ->all();

        return new PaginatedResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
        );
    }

    public function remove(GuestProfile $profile): void
    {
        $this->model->newQuery()
            ->where('uuid', $profile->uuid->value)
            ->delete();
    }

    public function nextIdentity(): GuestProfileId
    {
        return GuestProfileId::generate();
    }

    private function toEntity(object $record): GuestProfile
    {
        return GuestProfileReflector::reconstruct(
            uuid: GuestProfileId::fromString($record->uuid),
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

    private function toRecord(GuestProfile $profile): array
    {
        return [
            'uuid' => $profile->uuid->value,
            'full_name' => $profile->fullName,
            'email' => $profile->email,
            'phone' => $profile->phone,
            'document' => $profile->document,
            'loyalty_tier' => $profile->loyaltyTier->value,
            'preferences' => json_encode($profile->preferences),
            'created_at' => $profile->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $profile->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
