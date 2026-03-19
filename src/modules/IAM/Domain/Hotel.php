<?php

declare(strict_types=1);

namespace Modules\IAM\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\Event\HotelCreated;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Hotel extends AggregateRoot
{
    private function __construct(
        public readonly HotelId $uuid,
        public readonly AccountId $accountId,
        private(set) string $name,
        private(set) string $slug,
        private(set) ?string $description,
        private(set) ?string $address,
        private(set) ?string $contactEmail,
        private(set) ?string $contactPhone,
        private(set) string $status,
        public readonly DateTimeImmutable $createdAt,
        private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    public static function create(
        HotelId $uuid,
        AccountId $accountId,
        string $name,
        string $slug,
        DateTimeImmutable $createdAt,
        ?string $description = null,
        ?string $address = null,
        ?string $contactEmail = null,
        ?string $contactPhone = null,
    ): self {
        $hotel = new self(
            uuid: $uuid,
            accountId: $accountId,
            name: $name,
            slug: $slug,
            description: $description,
            address: $address,
            contactEmail: $contactEmail,
            contactPhone: $contactPhone,
            status: 'active',
            createdAt: $createdAt,
        );

        $hotel->recordEvent(new HotelCreated($uuid, $name));

        return $hotel;
    }

    public function updateProfile(
        string $name,
        string $slug,
        ?string $description,
        ?string $address,
        ?string $contactEmail,
        ?string $contactPhone,
    ): void {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->address = $address;
        $this->contactEmail = $contactEmail;
        $this->contactPhone = $contactPhone;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function id(): Identity
    {
        return $this->uuid;
    }
}
