<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain\ValueObject;

use InvalidArgumentException;
use Modules\Reservation\Domain\ValueObject\Email;
use Modules\Reservation\Domain\ValueObject\Guest;
use Modules\Reservation\Domain\ValueObject\Phone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GuestTest extends TestCase
{
    #[Test]
    public function it_creates_a_guest(): void
    {
        $guest = Guest::create(
            'John Doe',
            Email::fromString('john@hotel.com'),
            Phone::fromString('+5511999999999'),
            '12345678900',
        );

        $this->assertSame('John Doe', $guest->fullName);
        $this->assertSame('john@hotel.com', $guest->email->value);
        $this->assertSame('+5511999999999', $guest->phone->value);
        $this->assertSame('12345678900', $guest->document);
        $this->assertFalse($guest->isVip);
    }

    #[Test]
    public function it_creates_a_vip_guest(): void
    {
        $guest = Guest::create(
            'VIP Guest',
            Email::fromString('vip@hotel.com'),
            Phone::fromString('+5511999999999'),
            '12345678900',
            true,
        );

        $this->assertTrue($guest->isVip);
    }

    #[Test]
    public function it_rejects_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Guest full name cannot be empty');

        Guest::create(
            '',
            Email::fromString('john@hotel.com'),
            Phone::fromString('+5511999999999'),
            '12345678900',
        );
    }

    #[Test]
    public function it_rejects_empty_document(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Guest document cannot be empty');

        Guest::create(
            'John Doe',
            Email::fromString('john@hotel.com'),
            Phone::fromString('+5511999999999'),
            '',
        );
    }

    #[Test]
    public function it_compares_equal_guests(): void
    {
        $a = Guest::create('John', Email::fromString('john@hotel.com'), Phone::fromString('+5511999999999'), '123');
        $b = Guest::create('John', Email::fromString('john@hotel.com'), Phone::fromString('+5511999999999'), '123');

        $this->assertTrue($a->equals($b));
    }

    #[Test]
    public function it_compares_different_guests(): void
    {
        $a = Guest::create('John', Email::fromString('john@hotel.com'), Phone::fromString('+5511999999999'), '123');
        $b = Guest::create('Jane', Email::fromString('jane@hotel.com'), Phone::fromString('+5511888888888'), '456');

        $this->assertFalse($a->equals($b));
    }
}
