<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Shared\Domain\PaginatedResult;
use Modules\Shared\Infrastructure\Persistence\BaseRepository;

/**
 * @extends BaseRepository<GuestProfile>
 */
final class QueryBuilderGuestProfileRepository extends BaseRepository implements GuestProfileRepository
{
    protected function tableName(): string
    {
        return 'guest_profiles';
    }

    protected function toEntity(object $record): GuestProfile
    {
        return GuestProfileReflector::reconstruct(
            uuid: GuestProfileId::fromString($record->uuid),
            fullName: $record->full_name,
            email: $record->email,
            phone: $record->phone,
            document: $record->document,
            loyaltyTier: LoyaltyTier::from($record->loyalty_tier),
            preferences: json_decode($record->preferences ?? '[]', true),
            createdAt: new DateTimeImmutable($record->created_at),
            updatedAt: $record->updated_at ? new DateTimeImmutable($record->updated_at) : null,
        );
    }

    protected function toRecord(object $entity): array
    {
        /** @var GuestProfile $entity */
        return [
            'uuid' => $entity->uuid()->value,
            'full_name' => $entity->fullName(),
            'email' => $entity->email(),
            'phone' => $entity->phone(),
            'document' => $entity->document(),
            'loyalty_tier' => $entity->loyaltyTier()->value,
            'preferences' => json_encode($entity->preferences()),
            'created_at' => $entity->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $entity->updatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    // --- GuestProfileRepository contract ---

    public function findByUuid(GuestProfileId|string $uuid): ?GuestProfile
    {
        $uuidString = $uuid instanceof GuestProfileId ? $uuid->value : $uuid;

        return parent::findByUuid($uuidString);
    }

    public function findByEmail(string $email): ?GuestProfile
    {
        $record = DB::table($this->tableName())
            ->where('email', $email)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByDocument(string $document): ?GuestProfile
    {
        $record = DB::table($this->tableName())
            ->where('document', $document)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function paginate(int $page = 1, int $perPage = 15): PaginatedResult
    {
        $paginator = DB::table($this->tableName())
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

    public function nextIdentity(): GuestProfileId
    {
        return GuestProfileId::generate();
    }
}
