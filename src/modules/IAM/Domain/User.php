<?php

declare(strict_types=1);

namespace Modules\IAM\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\Event\UserContactInfoUpdated;
use Modules\IAM\Domain\Event\UserCreated;
use Modules\IAM\Domain\Event\UserLoyaltyTierChanged;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class User extends AggregateRoot
{
    /**
     * @param  string[]  $preferences
     */
    private function __construct(
        public readonly UserId $uuid,
        private(set) string $fullName,
        private(set) string $email,
        private(set) string $phone,
        private(set) string $document,
        private(set) ?LoyaltyTier $loyaltyTier,
        private(set) array $preferences,
        public readonly DateTimeImmutable $createdAt,
        private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * @param  string[]  $preferences
     */
    public static function create(
        UserId $uuid,
        string $fullName,
        string $email,
        string $phone,
        string $document,
        ?LoyaltyTier $loyaltyTier,
        array $preferences,
        DateTimeImmutable $createdAt,
    ): self {
        $user = new self(
            uuid: $uuid,
            fullName: $fullName,
            email: $email,
            phone: $phone,
            document: $document,
            loyaltyTier: $loyaltyTier,
            preferences: $preferences,
            createdAt: $createdAt,
        );

        $user->recordEvent(new UserCreated($uuid, $email));

        return $user;
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
        $this->updatedAt = new DateTimeImmutable;
        $this->recordEvent(new UserContactInfoUpdated($this->uuid));
    }

    public function changeLoyaltyTier(LoyaltyTier $tier): void
    {
        $this->loyaltyTier = $tier;
        $this->updatedAt = new DateTimeImmutable;
        $this->recordEvent(new UserLoyaltyTierChanged($this->uuid, $tier));
    }

    /** @param string[] $preferences */
    public function setPreferences(array $preferences): void
    {
        $this->preferences = $preferences;
        $this->updatedAt = new DateTimeImmutable;
    }
}
