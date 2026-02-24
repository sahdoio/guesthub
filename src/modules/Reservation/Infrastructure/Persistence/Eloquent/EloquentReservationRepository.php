<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\Reservation\Domain\Entity\SpecialRequest;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Infrastructure\Persistence\ReservationReflector;
use Modules\Reservation\Infrastructure\Persistence\SpecialRequestReflector;
use Modules\Shared\Domain\PaginatedResult;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Reservation\Domain\ValueObject\ReservationStatus;
use Modules\Reservation\Domain\ValueObject\RequestStatus;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;

final class EloquentReservationRepository implements ReservationRepository
{
    public function __construct(
        private readonly ReservationModel $model,
    ) {}

    public function save(Reservation $reservation): void
    {
        $data = $this->toRecord($reservation);

        $this->model->newQuery()
            ->updateOrInsert(['uuid' => $data['uuid']], $data);
    }

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
    ): PaginatedResult {
        $query = $this->model->newQuery()->orderByDesc('id');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($roomType !== null) {
            $query->where('room_type', $roomType);
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

    private function toRecord(Reservation $reservation): array
    {
        return [
            'uuid' => $reservation->uuid->value,
            'status' => $reservation->status->value,
            'guest_profile_id' => $reservation->guestProfileId,
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

    private function toEntity(object $record): Reservation
    {
        return ReservationReflector::reconstruct(
            uuid: ReservationId::fromString($record->uuid),
            guestProfileId: $record->guest_profile_id,
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
