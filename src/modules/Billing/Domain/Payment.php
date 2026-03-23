<?php

declare(strict_types=1);

namespace Modules\Billing\Domain;

use DateTimeImmutable;
use Modules\Billing\Domain\ValueObject\Money;
use Modules\Billing\Domain\ValueObject\PaymentMethod;
use Modules\Billing\Domain\ValueObject\PaymentStatus;
use Modules\Shared\Domain\Entity;
use Modules\Shared\Domain\Identity;

final class Payment extends Entity
{
    private function __construct(
        public readonly PaymentId $id,
        public readonly Money $amount,
        private(set) PaymentStatus $status,
        public readonly PaymentMethod $method,
        public readonly ?string $stripePaymentIntentId,
        public readonly DateTimeImmutable $createdAt,
        private(set) ?DateTimeImmutable $succeededAt,
        private(set) ?DateTimeImmutable $failedAt,
        private(set) ?string $failureReason,
    ) {}

    public static function create(
        PaymentId $id,
        Money $amount,
        PaymentMethod $method,
        ?string $stripePaymentIntentId,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            amount: $amount,
            status: PaymentStatus::PENDING,
            method: $method,
            stripePaymentIntentId: $stripePaymentIntentId,
            createdAt: $createdAt,
            succeededAt: null,
            failedAt: null,
            failureReason: null,
        );
    }

    public function id(): Identity
    {
        return $this->id;
    }

    public function markSucceeded(): void
    {
        $this->status = PaymentStatus::SUCCEEDED;
        $this->succeededAt = new DateTimeImmutable;
    }

    public function markFailed(string $reason): void
    {
        $this->status = PaymentStatus::FAILED;
        $this->failedAt = new DateTimeImmutable;
        $this->failureReason = $reason;
    }

    public function markRefunded(): void
    {
        $this->status = PaymentStatus::REFUNDED;
    }
}
