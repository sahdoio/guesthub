<?php

declare(strict_types=1);

namespace Modules\Guest\Domain;

use DateTimeImmutable;
use Modules\Guest\Domain\Event\GuestContactInfoUpdated;
use Modules\Guest\Domain\Event\GuestCreated;
use Modules\Guest\Domain\Event\GuestLoyaltyTierChanged;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Guest extends AggregateRoot
{
    /**
     * @param string[] $preferences
     */
    private function __construct(
        public readonly GuestId $uuid,
        private(set) string $fullName,
        private(set) string $email,
        private(set) string $phone,
        private(set) string $document,
        private(set) LoyaltyTier $loyaltyTier,
        private(set) array $preferences,
        public readonly DateTimeImmutable $createdAt,
        private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * @param string[] $preferences
     */
    public static function create(
        GuestId $uuid,
        string $fullName,
        string $email,
        string $phone,
        string $document,
        LoyaltyTier $loyaltyTier,
        array $preferences,
        DateTimeImmutable $createdAt,
    ): self {
        $guest = new self(
            uuid: $uuid,
            fullName: $fullName,
            email: $email,
            phone: $phone,
            document: $document,
            loyaltyTier: $loyaltyTier,
            preferences: $preferences,
            createdAt: $createdAt,
        );

        $guest->recordEvent(new GuestCreated($uuid, $email));

        return $guest;
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
        $this->recordEvent(new GuestContactInfoUpdated($this->uuid));
    }

    public function changeLoyaltyTier(LoyaltyTier $tier): void
    {
        $this->loyaltyTier = $tier;
        $this->updatedAt = new DateTimeImmutable();
        $this->recordEvent(new GuestLoyaltyTierChanged($this->uuid, $tier));
    }

    /** @param string[] $preferences */
    public function setPreferences(array $preferences): void
    {
        $this->preferences = $preferences;
        $this->updatedAt = new DateTimeImmutable();
    }
}
