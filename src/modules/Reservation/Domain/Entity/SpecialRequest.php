<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Entity;

use DateTimeImmutable;
use Modules\Reservation\Domain\Exception\InvalidReservationStateException;
use Modules\Reservation\Domain\ValueObject\RequestStatus;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;
use Modules\Shared\Domain\Entity;
use Modules\Shared\Domain\Identity;

final class SpecialRequest extends Entity
{
    private RequestStatus $status;
    private ?DateTimeImmutable $fulfilledAt = null;

    public function __construct(
        private readonly SpecialRequestId $id,
        private readonly RequestType $type,
        private string $description,
        private readonly DateTimeImmutable $createdAt,
    ) {
        $this->status = RequestStatus::PENDING;
    }

    public function id(): Identity
    {
        return $this->id;
    }

    public function type(): RequestType
    {
        return $this->type;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function status(): RequestStatus
    {
        return $this->status;
    }

    public function fulfilledAt(): ?DateTimeImmutable
    {
        return $this->fulfilledAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function fulfill(): void
    {
        if ($this->status !== RequestStatus::PENDING) {
            throw InvalidReservationStateException::forRequestTransition($this->status, RequestStatus::FULFILLED);
        }

        $this->status = RequestStatus::FULFILLED;
        $this->fulfilledAt = new DateTimeImmutable();
    }

    public function cancel(): void
    {
        if ($this->status !== RequestStatus::PENDING) {
            throw InvalidReservationStateException::forRequestTransition($this->status, RequestStatus::CANCELLED);
        }

        $this->status = RequestStatus::CANCELLED;
    }

    public function changeDescription(string $newDescription): void
    {
        if (trim($newDescription) === '') {
            throw new \InvalidArgumentException('Description cannot be empty.');
        }

        $this->description = $newDescription;
    }
}
