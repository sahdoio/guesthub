<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain;

use DateTimeImmutable;
use Modules\Reservation\Domain\Exception\InvalidReservationStateException;
use Modules\Reservation\Domain\ValueObject\RequestStatus;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;
use Modules\Shared\Domain\Entity;

final class SpecialRequest extends Entity
{
    private function __construct(
        public readonly SpecialRequestId $id,
        public readonly RequestType $type,
        private(set) string $description,
        public readonly DateTimeImmutable $createdAt,
        private(set) RequestStatus $status,
        private(set) ?DateTimeImmutable $fulfilledAt,
    ) {}

    public static function create(
        SpecialRequestId $id,
        RequestType $type,
        string $description,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            type: $type,
            description: $description,
            createdAt: $createdAt,
            status: RequestStatus::PENDING,
            fulfilledAt: null,
        );
    }

    public function id(): SpecialRequestId
    {
        return $this->id;
    }

    public function fulfill(): void
    {
        if ($this->status !== RequestStatus::PENDING) {
            throw InvalidReservationStateException::forRequestTransition($this->status, RequestStatus::FULFILLED);
        }

        $this->status = RequestStatus::FULFILLED;
        $this->fulfilledAt = new DateTimeImmutable;
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
