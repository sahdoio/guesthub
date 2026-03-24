<?php

declare(strict_types=1);

namespace Modules\Stay\Domain;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;
use Modules\Stay\Domain\Event\StayCreated;
use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;

final class Stay extends AggregateRoot
{
    private function __construct(
        public readonly StayId $uuid,
        public readonly AccountId $accountId,
        private(set) string $name,
        private(set) string $slug,
        private(set) ?string $description,
        private(set) ?string $address,
        private(set) StayType $type,
        private(set) StayCategory $category,
        private(set) float $pricePerNight,
        private(set) int $capacity,
        private(set) ?string $contactEmail,
        private(set) ?string $contactPhone,
        private(set) string $status,
        private(set) ?array $amenities,
        private(set) ?string $coverImagePath,
        public readonly DateTimeImmutable $createdAt,
        private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    public static function create(
        StayId $uuid,
        AccountId $accountId,
        string $name,
        string $slug,
        StayType $type,
        StayCategory $category,
        float $pricePerNight,
        int $capacity,
        DateTimeImmutable $createdAt,
        ?string $description = null,
        ?string $address = null,
        ?string $contactEmail = null,
        ?string $contactPhone = null,
        ?array $amenities = null,
    ): self {
        $stay = new self(
            uuid: $uuid,
            accountId: $accountId,
            name: $name,
            slug: $slug,
            description: $description,
            address: $address,
            type: $type,
            category: $category,
            pricePerNight: $pricePerNight,
            capacity: $capacity,
            contactEmail: $contactEmail,
            contactPhone: $contactPhone,
            status: 'active',
            amenities: $amenities,
            coverImagePath: null,
            createdAt: $createdAt,
        );

        $stay->recordEvent(new StayCreated($uuid, $name));

        return $stay;
    }

    public function updateProfile(
        string $name,
        string $slug,
        StayType $type,
        StayCategory $category,
        float $pricePerNight,
        int $capacity,
        ?string $description,
        ?string $address,
        ?string $contactEmail,
        ?string $contactPhone,
        ?array $amenities,
    ): void {
        $this->name = $name;
        $this->slug = $slug;
        $this->type = $type;
        $this->category = $category;
        $this->pricePerNight = $pricePerNight;
        $this->capacity = $capacity;
        $this->description = $description;
        $this->address = $address;
        $this->contactEmail = $contactEmail;
        $this->contactPhone = $contactPhone;
        $this->amenities = $amenities;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function setCoverImage(?string $path): void
    {
        $this->coverImagePath = $path;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function id(): Identity
    {
        return $this->uuid;
    }
}
