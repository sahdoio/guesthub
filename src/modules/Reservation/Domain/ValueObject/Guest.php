<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\ValueObject;

use Modules\Shared\Domain\ValueObject;

final class Guest extends ValueObject
{
    public function __construct(
        public readonly string $fullName,
        public readonly Email $email,
        public readonly Phone $phone,
        public readonly string $document,
        public readonly bool $isVip,
    ) {
        if (trim($fullName) === '') {
            throw new \InvalidArgumentException('Guest full name cannot be empty.');
        }

        if (trim($document) === '') {
            throw new \InvalidArgumentException('Guest document cannot be empty.');
        }
    }

    public static function create(
        string $fullName,
        Email $email,
        Phone $phone,
        string $document,
        bool $isVip = false,
    ): self {
        return new self($fullName, $email, $phone, $document, $isVip);
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->fullName === $other->fullName
            && $this->email->equals($other->email)
            && $this->phone->equals($other->phone)
            && $this->document === $other->document
            && $this->isVip === $other->isVip;
    }
}
