<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain;

use DateTimeImmutable;
use Modules\Reservation\Domain\Entity\SpecialRequest;
use Modules\Reservation\Domain\Event\GuestCheckedIn;
use Modules\Reservation\Domain\Event\GuestCheckedOut;
use Modules\Reservation\Domain\Event\ReservationCancelled;
use Modules\Reservation\Domain\Event\ReservationConfirmed;
use Modules\Reservation\Domain\Event\ReservationCreated;
use Modules\Reservation\Domain\Event\SpecialRequestAdded;
use Modules\Reservation\Domain\Event\SpecialRequestFulfilled;
use Modules\Reservation\Domain\Exception\InvalidReservationStateException;
use Modules\Reservation\Domain\Exception\MaxSpecialRequestsExceededException;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Reservation\Domain\ValueObject\ReservationStatus;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Reservation extends AggregateRoot
{
    private const int MAX_SPECIAL_REQUESTS = 5;

    /**
     * @param SpecialRequest[] $specialRequests
     */
    private function __construct(
        public readonly ReservationId $uuid,
        public readonly string $guestProfileId,
        public readonly ReservationPeriod $period,
        public readonly string $roomType,
        public private(set) ReservationStatus $status,
        public private(set) ?string $assignedRoomNumber,
        public private(set) array $specialRequests,
        public readonly DateTimeImmutable $createdAt,
        public private(set) ?DateTimeImmutable $confirmedAt,
        public private(set) ?DateTimeImmutable $checkedInAt,
        public private(set) ?DateTimeImmutable $checkedOutAt,
        public private(set) ?DateTimeImmutable $cancelledAt,
        public private(set) ?string $cancellationReason,
    ) {}

    public static function create(
        ReservationId $uuid,
        string $guestProfileId,
        ReservationPeriod $period,
        string $roomType,
    ): self {
        $reservation = new self(
            uuid: $uuid,
            guestProfileId: $guestProfileId,
            period: $period,
            roomType: $roomType,
            status: ReservationStatus::PENDING,
            assignedRoomNumber: null,
            specialRequests: [],
            createdAt: new DateTimeImmutable(),
            confirmedAt: null,
            checkedInAt: null,
            checkedOutAt: null,
            cancelledAt: null,
            cancellationReason: null,
        );

        $reservation->recordEvent(new ReservationCreated($uuid));

        return $reservation;
    }

    // --- Identity ---

    public function id(): Identity
    {
        return $this->uuid;
    }

    // --- Behavior ---

    public function confirm(): void
    {
        if ($this->status !== ReservationStatus::PENDING) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CONFIRMED);
        }

        $this->status = ReservationStatus::CONFIRMED;
        $this->confirmedAt = new DateTimeImmutable();

        $this->recordEvent(new ReservationConfirmed($this->uuid));
    }

    public function checkIn(string $roomNumber): void
    {
        if ($this->status !== ReservationStatus::CONFIRMED) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CHECKED_IN);
        }

        $this->status = ReservationStatus::CHECKED_IN;
        $this->assignedRoomNumber = $roomNumber;
        $this->checkedInAt = new DateTimeImmutable();

        $this->recordEvent(new GuestCheckedIn($this->uuid, $roomNumber));
    }

    public function checkOut(): void
    {
        if ($this->status !== ReservationStatus::CHECKED_IN) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CHECKED_OUT);
        }

        $this->status = ReservationStatus::CHECKED_OUT;
        $this->checkedOutAt = new DateTimeImmutable();

        $this->recordEvent(new GuestCheckedOut($this->uuid));
    }

    public function cancel(string $reason): void
    {
        if (!in_array($this->status, [ReservationStatus::PENDING, ReservationStatus::CONFIRMED], true)) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CANCELLED);
        }

        $this->status = ReservationStatus::CANCELLED;
        $this->cancellationReason = $reason;
        $this->cancelledAt = new DateTimeImmutable();

        $this->recordEvent(new ReservationCancelled($this->uuid, $reason));
    }

    // --- Special Requests ---

    public function addSpecialRequest(RequestType $type, string $description): SpecialRequestId
    {
        if (in_array($this->status, [ReservationStatus::CANCELLED, ReservationStatus::CHECKED_OUT], true)) {
            throw InvalidReservationStateException::forTransition($this->status, $this->status);
        }

        if (count($this->specialRequests) >= self::MAX_SPECIAL_REQUESTS) {
            throw new MaxSpecialRequestsExceededException();
        }

        $requestId = SpecialRequestId::generate();
        $this->specialRequests[] = SpecialRequest::create($requestId, $type, $description, new DateTimeImmutable());

        $this->recordEvent(new SpecialRequestAdded($this->uuid, $requestId));

        return $requestId;
    }

    public function fulfillSpecialRequest(SpecialRequestId $requestId): void
    {
        $request = $this->findSpecialRequest($requestId);
        $request->fulfill();

        $this->recordEvent(new SpecialRequestFulfilled($this->uuid, $requestId));
    }

    public function removeSpecialRequest(SpecialRequestId $requestId): void
    {
        if ($this->status !== ReservationStatus::PENDING) {
            throw InvalidReservationStateException::forTransition($this->status, $this->status);
        }

        $this->specialRequests = array_values(
            array_filter(
                $this->specialRequests,
                fn(SpecialRequest $sr) => !$sr->id()->equals($requestId),
            )
        );
    }

    // --- Private ---

    private function findSpecialRequest(SpecialRequestId $requestId): SpecialRequest
    {
        foreach ($this->specialRequests as $request) {
            if ($request->id()->equals($requestId)) {
                return $request;
            }
        }

        throw new \DomainException("Special request '{$requestId}' not found.");
    }
}
