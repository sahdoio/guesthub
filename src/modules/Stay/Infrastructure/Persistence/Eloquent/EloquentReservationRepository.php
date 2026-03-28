<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence\Eloquent;

use DateMalformedStringException;
use DateTimeImmutable;
use Modules\Shared\Domain\PaginatedResult;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\SpecialRequest;
use Modules\Stay\Domain\ValueObject\RequestStatus;
use Modules\Stay\Domain\ValueObject\RequestType;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;
use Modules\Stay\Domain\ValueObject\ReservationStatus;
use Modules\Stay\Domain\ValueObject\SpecialRequestId;
use Modules\Stay\Infrastructure\Persistence\ReservationReflector;
use Modules\Stay\Infrastructure\Persistence\SpecialRequestReflector;

final readonly class EloquentReservationRepository implements ReservationRepository
{
    public function __construct(
        private ReservationModel $model,
    ) {}

    public function listByGuestId(
        string $guestId,
        int $page = 1,
        int $perPage = 15,
        ?string $status = null,
    ): PaginatedResult {
        $query = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('guest_id', $guestId)
            ->orderByDesc('id');

        if ($status !== null) {
            $query->where('status', $status);
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

    public function save(Reservation $reservation, int $accountNumericId): void
    {
        $data = $this->toRecord($reservation, $accountNumericId);

        $this->model->newQuery()
            ->updateOrInsert(['uuid' => $data['uuid']], $data);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function findByUuid(ReservationId $uuid): ?Reservation
    {
        $record = $this->model->newQuery()
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function findByUuidGlobal(ReservationId $uuid): ?Reservation
    {
        $record = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function list(
        int $page = 1,
        int $perPage = 15,
        ?string $status = null,
        ?string $guestId = null,
        ?string $stayId = null,
    ): PaginatedResult {
        $query = $this->model->newQuery()->orderByDesc('id');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($guestId !== null) {
            $query->where('guest_id', $guestId);
        }

        if ($stayId !== null) {
            $query->where('stay_uuid', $stayId);
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

    public function nextIdentity(): ReservationId
    {
        return ReservationId::generate();
    }

    public function count(): int
    {
        return (int) $this->model->newQuery()->count();
    }

    public function countByStatus(): array
    {
        return $this->model->newQuery()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    public function countTodayCheckIns(): int
    {
        return (int) $this->model->newQuery()
            ->whereDate('checked_in_at', now()->toDateString())
            ->count();
    }

    public function countTodayCheckOuts(): int
    {
        return (int) $this->model->newQuery()
            ->whereDate('checked_out_at', now()->toDateString())
            ->count();
    }

    public function listUpcoming(
        int $page = 1,
        int $perPage = 10,
    ): PaginatedResult {
        $query = $this->model->newQuery()
            ->where('check_in', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->orderBy('check_in');

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

    public function listUpcomingByGuestId(
        string $guestId,
        int $page = 1,
        int $perPage = 5,
    ): PaginatedResult {
        $query = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('guest_id', $guestId)
            ->where('check_in', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->orderBy('check_in');

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

    private function toRecord(Reservation $reservation, int $accountNumericId): array
    {
        return [
            'uuid' => $reservation->uuid->value,
            'account_id' => $accountNumericId,
            'account_uuid' => $reservation->accountId,
            'stay_id' => $this->resolveStayNumericId($reservation->stayId),
            'stay_uuid' => $reservation->stayId,
            'status' => $reservation->status->value,
            'guest_id' => $reservation->guestId,
            'check_in' => $reservation->period->checkIn->format('Y-m-d'),
            'check_out' => $reservation->period->checkOut->format('Y-m-d'),
            'adults' => $reservation->adults,
            'children' => $reservation->children,
            'babies' => $reservation->babies,
            'pets' => $reservation->pets,
            'special_requests' => json_encode($this->serializeSpecialRequests($reservation->specialRequests)),
            'cancellation_reason' => $reservation->cancellationReason,
            'created_at' => $reservation->createdAt->format('Y-m-d H:i:s'),
            'confirmed_at' => $reservation->confirmedAt?->format('Y-m-d H:i:s'),
            'checked_in_at' => $reservation->checkedInAt?->format('Y-m-d H:i:s'),
            'checked_out_at' => $reservation->checkedOutAt?->format('Y-m-d H:i:s'),
            'cancelled_at' => $reservation->cancelledAt?->format('Y-m-d H:i:s'),
            'free_cancellation_until' => $reservation->freeCancellationUntil?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @throws DateMalformedStringException
     */
    private function toEntity(object $record): Reservation
    {
        return ReservationReflector::reconstruct(
            uuid: ReservationId::fromString($record->uuid),
            guestId: $record->guest_id,
            accountId: $record->account_uuid ?? '',
            stayId: $record->stay_uuid ?? '',
            period: new ReservationPeriod(
                new DateTimeImmutable($record->check_in),
                new DateTimeImmutable($record->check_out),
            ),
            adults: (int) ($record->adults ?? 1),
            children: (int) ($record->children ?? 0),
            babies: (int) ($record->babies ?? 0),
            pets: (int) ($record->pets ?? 0),
            status: ReservationStatus::from($record->status),
            specialRequests: $this->deserializeSpecialRequests(
                is_array($record->special_requests) ? json_encode($record->special_requests) : $record->special_requests,
            ),
            createdAt: new DateTimeImmutable($record->created_at),
            confirmedAt: $record->confirmed_at ? new DateTimeImmutable($record->confirmed_at) : null,
            checkedInAt: $record->checked_in_at ? new DateTimeImmutable($record->checked_in_at) : null,
            checkedOutAt: $record->checked_out_at ? new DateTimeImmutable($record->checked_out_at) : null,
            cancelledAt: $record->cancelled_at ? new DateTimeImmutable($record->cancelled_at) : null,
            cancellationReason: $record->cancellation_reason,
            freeCancellationUntil: $record->free_cancellation_until ? new DateTimeImmutable($record->free_cancellation_until) : null,
        );
    }

    private function resolveStayNumericId(string $stayUuid): ?int
    {
        if ($stayUuid === '') {
            return null;
        }

        $id = StayModel::query()->withoutGlobalScopes()->where('uuid', $stayUuid)->value('id');

        return $id !== null ? (int) $id : null;
    }

    /** @param SpecialRequest[] $requests */
    private function serializeSpecialRequests(array $requests): array
    {
        return array_map(fn (SpecialRequest $sr) => [
            'id' => (string) $sr->id,
            'type' => $sr->type->value,
            'description' => $sr->description,
            'status' => $sr->status->value,
            'fulfilled_at' => $sr->fulfilledAt?->format('Y-m-d H:i:s'),
            'created_at' => $sr->createdAt->format('Y-m-d H:i:s'),
        ], $requests);
    }

    /**
     * @return SpecialRequest[]
     *
     * @throws DateMalformedStringException
     */
    private function deserializeSpecialRequests(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $items = json_decode($json, true) ?: [];

        return array_map(function (array $item) {
            return SpecialRequestReflector::reconstruct(
                id: SpecialRequestId::fromString($item['id']),
                type: RequestType::from($item['type']),
                description: $item['description'],
                status: RequestStatus::from($item['status']),
                createdAt: new DateTimeImmutable($item['created_at']),
                fulfilledAt: isset($item['fulfilled_at']) ? new DateTimeImmutable($item['fulfilled_at']) : null,
            );
        }, $items);
    }
}
