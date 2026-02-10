<?php

declare(strict_types=1);

namespace Modules\Guest\Domain;

use DateTimeImmutable;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class GuestProfile extends AggregateRoot
{
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * @param string[] $preferences
     */
    public function __construct(
        private readonly GuestProfileId $uuid,
        private string $fullName,
        private string $email,
        private string $phone,
        private string $document,
        private LoyaltyTier $loyaltyTier,
        private array $preferences,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    // --- Identity ---

    public function id(): Identity
    {
        return $this->uuid;
    }

    public function uuid(): GuestProfileId
    {
        return $this->uuid;
    }

    // --- Getters ---

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function phone(): string
    {
        return $this->phone;
    }

    public function document(): string
    {
        return $this->document;
    }

    public function loyaltyTier(): LoyaltyTier
    {
        return $this->loyaltyTier;
    }

    /** @return string[] */
    public function preferences(): array
    {
        return $this->preferences;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // --- Behavior ---

    public function updateContactInfo(string $fullName, string $email, string $phone): void
    {
        $this->fullName = $fullName;
        $this->email = $email;
        $this->phone = $phone;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function changeLoyaltyTier(LoyaltyTier $tier): void
    {
        $this->loyaltyTier = $tier;
        $this->updatedAt = new DateTimeImmutable();
    }

    /** @param string[] $preferences */
    public function setPreferences(array $preferences): void
    {
        $this->preferences = $preferences;
        $this->updatedAt = new DateTimeImmutable();
    }
}
