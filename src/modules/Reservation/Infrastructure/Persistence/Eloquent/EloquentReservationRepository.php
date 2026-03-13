<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Persistence\Eloquent;

use DateMalformedStringException;
use DateTimeImmutable;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\SpecialRequest;
use Modules\Reservation\Domain\ValueObject\RequestStatus;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Reservation\Domain\ValueObject\ReservationStatus;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;
use Modules\Reservation\Infrastructure\Persistence\ReservationReflector;
use Modules\Reservation\Infrastructure\Persistence\SpecialRequestReflector;
use Modules\Shared\Domain\PaginatedResult;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final readonly class EloquentReservationRepository implements ReservationRepository
{
    public function __construct(
        private ReservationModel $model,
        private TenantContext $tenantContext,
    ) {}

    public function save(Reservation $reservation): void
    {
        $data = $this->toRecord($reservation);

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

    public function list(
        int $page = 1,
        int $perPage = 15,
        ?string $status = null,
        ?string $roomType = null,
        ?string $guestId = null,
    ): PaginatedResult {
        $query = $this->model->newQuery()->orderByDesc('id');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($roomType !== null) {
            $query->where('room_type', $roomType);
        }

        if ($guestId !== null) {
            $query->where('guest_id', $guestId);
        }

        $paginator = $query->paginate(perPage: $perPage, page: $page);

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

    public function countByRoomType(): array
    {
        return $this->model->newQuery()
            ->selectRaw('room_type, count(*) as total')
            ->groupBy('room_type')
            ->pluck('total', 'room_type')
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

    private function toRecord(Reservation $reservation): array
    {
        return [
            'uuid' => $reservation->uuid->value,
            'account_id' => $this->tenantContext->id(),
            'status' => $reservation->status->value,
            'guest_id' => $reservation->guestId,
            'check_in' => $reservation->period->checkIn->format('Y-m-d'),
            'check_out' => $reservation->period->checkOut->format('Y-m-d'),
            'room_type' => $reservation->roomType,
            'assigned_room_number' => $reservation->assignedRoomNumber,
            'special_requests' => json_encode($this->serializeSpecialRequests($reservation->specialRequests)),
            'cancellation_reason' => $reservation->cancellationReason,
            'created_at' => $reservation->createdAt->format('Y-m-d H:i:s'),
            'confirmed_at' => $reservation->confirmedAt?->format('Y-m-d H:i:s'),
            'checked_in_at' => $reservation->checkedInAt?->format('Y-m-d H:i:s'),
            'checked_out_at' => $reservation->checkedOutAt?->format('Y-m-d H:i:s'),
            'cancelled_at' => $reservation->cancelledAt?->format('Y-m-d H:i:s'),
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
            period: new ReservationPeriod(
                new DateTimeImmutable($record->check_in),
                new DateTimeImmutable($record->check_out),
            ),
            roomType: $record->room_type,
            status: ReservationStatus::from($record->status),
            assignedRoomNumber: $record->assigned_room_number,
            specialRequests: $this->deserializeSpecialRequests(
                is_array($record->special_requests) ? json_encode($record->special_requests) : $record->special_requests,
            ),
            createdAt: new DateTimeImmutable($record->created_at),
            confirmedAt: $record->confirmed_at ? new DateTimeImmutable($record->confirmed_at) : null,
            checkedInAt: $record->checked_in_at ? new DateTimeImmutable($record->checked_in_at) : null,
            checkedOutAt: $record->checked_out_at ? new DateTimeImmutable($record->checked_out_at) : null,
            cancelledAt: $record->cancelled_at ? new DateTimeImmutable($record->cancelled_at) : null,
            cancellationReason: $record->cancellation_reason,
        );
    }

    /** @param SpecialRequest[] $requests */
    private function serializeSpecialRequests(array $requests): array
    {
        return array_map(fn(SpecialRequest $sr) => [
            'id' => (string) $sr->id,
            'type' => $sr->type->value,
            'description' => $sr->description,
            'status' => $sr->status->value,
            'fulfilled_at' => $sr->fulfilledAt?->format('Y-m-d H:i:s'),
            'created_at' => $sr->createdAt->format('Y-m-d H:i:s'),
        ], $requests);
    }

    /** @return SpecialRequest[] */
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
