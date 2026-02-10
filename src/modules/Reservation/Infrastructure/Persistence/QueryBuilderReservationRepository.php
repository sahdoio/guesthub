<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Reservation\Domain\Entity\SpecialRequest;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\ValueObject\Email;
use Modules\Shared\Domain\PaginatedResult;
use Modules\Reservation\Domain\ValueObject\Guest;
use Modules\Reservation\Domain\ValueObject\Phone;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Reservation\Domain\ValueObject\ReservationStatus;
use Modules\Reservation\Domain\ValueObject\RequestStatus;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;

final class QueryBuilderReservationRepository implements ReservationRepository
{
    private const string TABLE = 'reservations';

    public function save(Reservation $reservation): void
    {
        $data = $this->toRecord($reservation);

        $existing = DB::table(self::TABLE)
            ->where('uuid', $reservation->uuid()->value)
            ->first();

        if ($existing) {
            DB::table(self::TABLE)
                ->where('id', $existing->id)
                ->update($data);
        } else {
            DB::table(self::TABLE)->insert($data);
        }
    }

    public function findByUuid(ReservationId $uuid): ?Reservation
    {
        $record = DB::table(self::TABLE)
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByGuestEmail(Email $email): array
    {
        return DB::table(self::TABLE)
            ->where('guest_email', $email->value)
            ->get()
            ->map(fn(object $record) => $this->toEntity($record))
            ->all();
    }

    public function paginate(int $page = 1, int $perPage = 15): PaginatedResult
    {
        $paginator = DB::table(self::TABLE)
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

    public function nextIdentity(): ReservationId
    {
        return ReservationId::generate();
    }

    private function toRecord(Reservation $reservation): array
    {
        return [
            'uuid' => $reservation->uuid()->value,
            'status' => $reservation->status()->value,
            'guest_full_name' => $reservation->guest()->fullName,
            'guest_email' => $reservation->guest()->email->value,
            'guest_phone' => $reservation->guest()->phone->value,
            'guest_document' => $reservation->guest()->document,
            'guest_is_vip' => $reservation->guest()->isVip,
            'check_in' => $reservation->period()->checkIn->format('Y-m-d'),
            'check_out' => $reservation->period()->checkOut->format('Y-m-d'),
            'room_type' => $reservation->roomType(),
            'assigned_room_number' => $reservation->assignedRoomNumber(),
            'special_requests' => json_encode($this->serializeSpecialRequests($reservation->specialRequests())),
            'cancellation_reason' => $reservation->cancellationReason(),
            'created_at' => $reservation->createdAt()->format('Y-m-d H:i:s'),
            'confirmed_at' => $reservation->confirmedAt()?->format('Y-m-d H:i:s'),
            'checked_in_at' => $reservation->checkedInAt()?->format('Y-m-d H:i:s'),
            'checked_out_at' => $reservation->checkedOutAt()?->format('Y-m-d H:i:s'),
            'cancelled_at' => $reservation->cancelledAt()?->format('Y-m-d H:i:s'),
        ];
    }

    private function toEntity(object $record): Reservation
    {
        return ReservationReflector::reconstruct(
            uuid: ReservationId::fromString($record->uuid),
            guest: Guest::create(
                $record->guest_full_name,
                Email::fromString($record->guest_email),
                Phone::fromString($record->guest_phone),
                $record->guest_document,
                (bool) $record->guest_is_vip,
            ),
            period: new ReservationPeriod(
                new DateTimeImmutable($record->check_in),
                new DateTimeImmutable($record->check_out),
            ),
            roomType: $record->room_type,
            status: ReservationStatus::from($record->status),
            assignedRoomNumber: $record->assigned_room_number,
            specialRequests: $this->deserializeSpecialRequests($record->special_requests),
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
            'id' => (string) $sr->id(),
            'type' => $sr->type()->value,
            'description' => $sr->description(),
            'status' => $sr->status()->value,
            'fulfilled_at' => $sr->fulfilledAt()?->format('Y-m-d H:i:s'),
            'created_at' => $sr->createdAt()->format('Y-m-d H:i:s'),
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
