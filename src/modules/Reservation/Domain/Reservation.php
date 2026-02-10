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
use Modules\Reservation\Domain\ValueObject\Email;
use Modules\Reservation\Domain\ValueObject\Guest;
use Modules\Reservation\Domain\ValueObject\Phone;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Reservation\Domain\ValueObject\ReservationStatus;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Reservation extends AggregateRoot
{
    private const int MAX_SPECIAL_REQUESTS = 5;

    private ReservationStatus $status;
    private ?string $assignedRoomNumber = null;
    /** @var SpecialRequest[] */
    private array $specialRequests = [];
    private readonly DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $confirmedAt = null;
    private ?DateTimeImmutable $checkedInAt = null;
    private ?DateTimeImmutable $checkedOutAt = null;
    private ?DateTimeImmutable $cancelledAt = null;
    private ?string $cancellationReason = null;

    public function __construct(
        private readonly ReservationId $id,
        private Guest $guest,
        private readonly ReservationPeriod $period,
        private readonly string $roomType,
    ) {
        $this->status = ReservationStatus::PENDING;
        $this->createdAt = new DateTimeImmutable();

        $this->recordEvent(new ReservationCreated($this->id));
    }

    // --- Identity ---

    public function id(): Identity
    {
        return $this->id;
    }

    // --- Getters ---

    public function reservationId(): ReservationId
    {
        return $this->id;
    }

    public function status(): ReservationStatus
    {
        return $this->status;
    }

    public function guest(): Guest
    {
        return $this->guest;
    }

    public function period(): ReservationPeriod
    {
        return $this->period;
    }

    public function roomType(): string
    {
        return $this->roomType;
    }

    public function assignedRoomNumber(): ?string
    {
        return $this->assignedRoomNumber;
    }

    /** @return SpecialRequest[] */
    public function specialRequests(): array
    {
        return $this->specialRequests;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function confirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function checkedInAt(): ?DateTimeImmutable
    {
        return $this->checkedInAt;
    }

    public function checkedOutAt(): ?DateTimeImmutable
    {
        return $this->checkedOutAt;
    }

    public function cancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function cancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    // --- Behavior ---

    public function confirm(): void
    {
        if ($this->status !== ReservationStatus::PENDING) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CONFIRMED);
        }

        $this->status = ReservationStatus::CONFIRMED;
        $this->confirmedAt = new DateTimeImmutable();

        $this->recordEvent(new ReservationConfirmed($this->id));
    }

    public function checkIn(string $roomNumber): void
    {
        if ($this->status !== ReservationStatus::CONFIRMED) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CHECKED_IN);
        }

        $this->status = ReservationStatus::CHECKED_IN;
        $this->assignedRoomNumber = $roomNumber;
        $this->checkedInAt = new DateTimeImmutable();

        $this->recordEvent(new GuestCheckedIn($this->id, $roomNumber));
    }

    public function checkOut(): void
    {
        if ($this->status !== ReservationStatus::CHECKED_IN) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CHECKED_OUT);
        }

        $this->status = ReservationStatus::CHECKED_OUT;
        $this->checkedOutAt = new DateTimeImmutable();

        $this->recordEvent(new GuestCheckedOut($this->id));
    }

    public function cancel(string $reason): void
    {
        if (!in_array($this->status, [ReservationStatus::PENDING, ReservationStatus::CONFIRMED], true)) {
            throw InvalidReservationStateException::forTransition($this->status, ReservationStatus::CANCELLED);
        }

        $this->status = ReservationStatus::CANCELLED;
        $this->cancellationReason = $reason;
        $this->cancelledAt = new DateTimeImmutable();

        $this->recordEvent(new ReservationCancelled($this->id, $reason));
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
        $this->specialRequests[] = new SpecialRequest($requestId, $type, $description, new DateTimeImmutable());

        $this->recordEvent(new SpecialRequestAdded($this->id, $requestId));

        return $requestId;
    }

    public function fulfillSpecialRequest(SpecialRequestId $requestId): void
    {
        $request = $this->findSpecialRequest($requestId);
        $request->fulfill();

        $this->recordEvent(new SpecialRequestFulfilled($this->id, $requestId));
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

    public function changeGuestContact(Email $email, Phone $phone): void
    {
        $this->guest = Guest::create(
            $this->guest->fullName,
            $email,
            $phone,
            $this->guest->document,
            $this->guest->isVip,
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
