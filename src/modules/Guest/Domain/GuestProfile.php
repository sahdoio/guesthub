<?php

declare(strict_types=1);

namespace Modules\Guest\Domain;

use DateTimeImmutable;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class GuestProfile extends AggregateRoot
{
    /**
     * @param string[] $preferences
     */
    private function __construct(
        public readonly GuestProfileId $uuid,
        public private(set) string $fullName,
        public private(set) string $email,
        public private(set) string $phone,
        public private(set) string $document,
        public private(set) LoyaltyTier $loyaltyTier,
        public private(set) array $preferences,
        public readonly DateTimeImmutable $createdAt,
        public private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * @param string[] $preferences
     */
    public static function create(
        GuestProfileId $uuid,
        string $fullName,
        string $email,
        string $phone,
        string $document,
        LoyaltyTier $loyaltyTier,
        array $preferences,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            uuid: $uuid,
            fullName: $fullName,
            email: $email,
            phone: $phone,
            document: $document,
            loyaltyTier: $loyaltyTier,
            preferences: $preferences,
            createdAt: $createdAt,
        );
    }

    public function id(): Identity
    {
        return $this->uuid;
    }

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
