<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\UserId;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Persistence\UserReflector;
use Modules\Shared\Domain\PaginatedResult;

final class EloquentUserRepository implements UserRepository
{
    public function __construct(
        private readonly UserModel $model,
    ) {}

    public function save(User $user): void
    {
        $data = $this->toRecord($user);

        $this->model->newQuery()
            ->updateOrInsert(['uuid' => $data['uuid']], $data);
    }

    public function findByUuid(UserId $uuid): ?User
    {
        $record = $this->model->newQuery()
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $record = $this->model->newQuery()
            ->where('email', $email)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByDocument(string $document): ?User
    {
        $record = $this->model->newQuery()
            ->where('document', $document)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function list(int $page = 1, int $perPage = 15, array $filters = []): PaginatedResult
    {
        $query = $this->model->newQuery()->orderByDesc('id');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['loyalty_tier'])) {
            $query->where('loyalty_tier', $filters['loyalty_tier']);
        }

        if (isset($filters['guest_uuids'])) {
            $query->whereIn('uuid', $filters['guest_uuids']);
        }

        $paginator = $query->paginate(perPage: $perPage, page: $page);

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

    public function remove(User $user): void
    {
        $this->model->newQuery()
            ->where('uuid', $user->uuid->value)
            ->delete();
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }

    public function findByNumericId(int $id): ?User
    {
        $record = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('id', $id)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function resolveNumericId(UserId $uuid): ?int
    {
        $id = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('uuid', $uuid->value)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    public function count(): int
    {
        return (int) $this->model->newQuery()->count();
    }

    public function countByLoyaltyTier(): array
    {
        return $this->model->newQuery()
            ->selectRaw('loyalty_tier, count(*) as total')
            ->whereNotNull('loyalty_tier')
            ->groupBy('loyalty_tier')
            ->pluck('total', 'loyalty_tier')
            ->all();
    }

    private function toEntity(object $record): User
    {
        return UserReflector::reconstruct(
            uuid: UserId::fromString($record->uuid),
            fullName: $record->full_name,
            email: $record->email,
            phone: $record->phone,
            document: $record->document,
            loyaltyTier: $record->loyalty_tier ? LoyaltyTier::from($record->loyalty_tier) : null,
            preferences: is_array($record->preferences) ? $record->preferences : json_decode($record->preferences ?? '[]', true),
            createdAt: new DateTimeImmutable($record->created_at),
            updatedAt: $record->updated_at ? new DateTimeImmutable($record->updated_at) : null,
        );
    }

    private function toRecord(User $user): array
    {
        return [
            'uuid' => $user->uuid->value,
            'full_name' => $user->fullName,
            'email' => $user->email,
            'phone' => $user->phone,
            'document' => $user->document,
            'loyalty_tier' => $user->loyaltyTier?->value,
            'preferences' => json_encode($user->preferences),
            'created_at' => $user->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $user->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
