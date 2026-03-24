<?php

declare(strict_types=1);

namespace Modules\Billing\Domain;

use Modules\Billing\Domain\ValueObject\Money;
use Modules\Shared\Domain\Entity;
use Modules\Shared\Domain\Identity;

final class LineItem extends Entity
{
    private function __construct(
        public readonly LineItemId $id,
        public readonly string $description,
        public readonly Money $unitPrice,
        public readonly int $quantity,
        public readonly Money $total,
    ) {}

    public static function create(
        LineItemId $id,
        string $description,
        Money $unitPrice,
        int $quantity,
    ): self {
        return new self(
            id: $id,
            description: $description,
            unitPrice: $unitPrice,
            quantity: $quantity,
            total: $unitPrice->multiply($quantity),
        );
    }

    public function id(): Identity
    {
        return $this->id;
    }
}
