<?php

declare(strict_types=1);

namespace Modules\Stay\Domain;

use DateTimeImmutable;
use Modules\Stay\Domain\Event\GuestCheckedIn;
use Modules\Stay\Domain\Event\GuestCheckedOut;
use Modules\Stay\Domain\Event\ReservationCancelled;
use Modules\Stay\Domain\Event\ReservationConfirmed;
use Modules\Stay\Domain\Event\ReservationCreated;
use Modules\Stay\Domain\Event\SpecialRequestAdded;
use Modules\Stay\Domain\Event\SpecialRequestFulfilled;
use Modules\Stay\Domain\Exception\InvalidReservationStateException;
use Modules\Stay\Domain\Exception\MaxSpecialRequestsExceededException;
use Modules\Stay\Domain\ValueObject\RequestType;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;
use Modules\Stay\Domain\ValueObject\ReservationStatus;
use Modules\Stay\Domain\ValueObject\SpecialRequestId;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Reservation extends AggregateRoot
{
    private const int MAX_SPECIAL_REQUESTS = 5;

    /**
     * @param  SpecialRequest[]  $specialRequests
     */
    private function __construct(
        public readonly ReservationId $uuid,
        public readonly string $guestId,
        public readonly string $accountId,
        public readonly string $stayId,
        public readonly ReservationPeriod $period,
        private(set) int $adults,
        private(set) int $children,
        private(set) int $babies,
        private(set) int $pets,
        private(set) ReservationStatus $status,
        private(set) array $specialRequests,
        public readonly DateTimeImmutable $createdAt,
        private(set) ?DateTimeImmutable $confirmedAt,
        private(set) ?DateTimeImmutable $checkedInAt,
        private(set) ?DateTimeImmutable $checkedOutAt,
        private(set) ?DateTimeImmutable $cancelledAt,
        private(set) ?string $cancellationReason,
        private(set) ?DateTimeImmutable $freeCancellationUntil,
    ) {}

    public static function create(
        ReservationId $uuid,
        string $guestId,
        string $accountId,
        string $stayId,
        ReservationPeriod $period,
        int $adults = 1,
        int $children = 0,
        int $babies = 0,
        int $pets = 0,
    ): self {
        $reservation = new self(
            uuid: $uuid,
            guestId: $guestId,
            accountId: $accountId,
            stayId: $stayId,
            period: $period,
            adults: $adults,
            children: $children,
            babies: $babies,
            pets: $pets,
            status: ReservationStatus::PENDING,
            specialRequests: [],
            createdAt: new DateTimeImmutable,
            confirmedAt: null,
            checkedInAt: null,
            checkedOutAt: null,
            cancelledAt: null,
            cancellationReason: null,
            freeCancellationUntil: $period->checkIn->modify('-48 hours'),
        );

        $reservation->recordEvent(new ReservationCreated($uuid));

        return $reservation;
    }

    public function id(): Identity
    {
        return $this->uuid;
    }

    public function confirm(): void
    {
        if ($this->status !== ReservationStatus::PENDING) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CONFIRMED);
        }

        $this->status = ReservationStatus::CONFIRMED;
        $this->confirmedAt = new DateTimeImmutable;

        $this->recordEvent(new ReservationConfirmed($this->uuid));
    }

    public function checkIn(): void
    {
        // business invariant
        if ($this->status !== ReservationStatus::CONFIRMED) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CHECKED_IN);
        }

        $this->status = ReservationStatus::CHECKED_IN;
        $this->checkedInAt = new DateTimeImmutable;

        $this->recordEvent(new GuestCheckedIn($this->uuid));
    }

    public function checkOut(): void
    {
        if ($this->status !== ReservationStatus::CHECKED_IN) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CHECKED_OUT);
        }

        $this->status = ReservationStatus::CHECKED_OUT;
        $this->checkedOutAt = new DateTimeImmutable;

        $this->recordEvent(new GuestCheckedOut($this->uuid));
    }

    public function cancel(string $reason): void
    {
        if (! in_array($this->status, [ReservationStatus::PENDING, ReservationStatus::CONFIRMED], true)) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CANCELLED);
        }

        $this->status = ReservationStatus::CANCELLED;
        $this->cancellationReason = $reason;
        $this->cancelledAt = new DateTimeImmutable;

        $this->recordEvent(new ReservationCancelled($this->uuid, $reason));
    }

    public function addSpecialRequest(RequestType $type, string $description): SpecialRequestId
    {
        if (in_array($this->status, [ReservationStatus::CANCELLED, ReservationStatus::CHECKED_OUT], true)) {
            throw InvalidReservationStateException::forTransition($this->status, $this->status);
        }

        if (count($this->specialRequests) >= self::MAX_SPECIAL_REQUESTS) {
            throw new MaxSpecialRequestsExceededException;
        }

        $requestId = SpecialRequestId::generate();
        $this->specialRequests[] = SpecialRequest::create($requestId, $type, $description, new DateTimeImmutable);

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
                fn (SpecialRequest $sr) => ! $sr->id()->equals($requestId),
            )
        );
    }

    public function isWithinFreeCancellationWindow(): bool
    {
        return $this->freeCancellationUntil !== null && new DateTimeImmutable() < $this->freeCancellationUntil;
    }

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
