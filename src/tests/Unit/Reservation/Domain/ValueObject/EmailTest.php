<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain\ValueObject;

use InvalidArgumentException;
use Modules\Reservation\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    #[Test]
    public function it_creates_a_valid_email(): void
    {
        $email = Email::fromString('guest@hotel.com');

        $this->assertSame('guest@hotel.com', $email->value);
    }

    #[Test]
    #[DataProvider('invalidEmails')]
    public function it_rejects_invalid_emails(string $invalid): void
    {
        $this->expectException(InvalidArgumentException::class);

        Email::fromString($invalid);
    }

    public static function invalidEmails(): array
    {
        return [
            'empty string' => [''],
            'no at sign' => ['guest-hotel.com'],
            'no domain' => ['guest@'],
            'no local part' => ['@hotel.com'],
            'spaces' => ['gu est@hotel.com'],
        ];
    }

    #[Test]
    public function it_compares_equal_emails(): void
    {
        $a = Email::fromString('guest@hotel.com');
        $b = Email::fromString('guest@hotel.com');

        $this->assertTrue($a->equals($b));
    }

    #[Test]
    public function it_compares_different_emails(): void
    {
        $a = Email::fromString('guest@hotel.com');
        $b = Email::fromString('other@hotel.com');

        $this->assertFalse($a->equals($b));
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $email = Email::fromString('guest@hotel.com');

        $this->assertSame('guest@hotel.com', (string) $email);
    }
}
