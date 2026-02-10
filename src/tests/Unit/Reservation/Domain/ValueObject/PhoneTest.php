<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain\ValueObject;

use InvalidArgumentException;
use Modules\Reservation\Domain\ValueObject\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PhoneTest extends TestCase
{
    #[Test]
    #[DataProvider('validPhones')]
    public function it_creates_valid_phones(string $number): void
    {
        $phone = Phone::fromString($number);

        $this->assertSame($number, $phone->value);
    }

    public static function validPhones(): array
    {
        return [
            'brazil mobile' => ['+5511999999999'],
            'us number' => ['+12025551234'],
            'uk number' => ['+442071234567'],
            'short valid' => ['+1234567'],
        ];
    }

    #[Test]
    #[DataProvider('invalidPhones')]
    public function it_rejects_invalid_phones(string $invalid): void
    {
        $this->expectException(InvalidArgumentException::class);

        Phone::fromString($invalid);
    }

    public static function invalidPhones(): array
    {
        return [
            'no plus' => ['5511999999999'],
            'starts with zero' => ['+0511999999999'],
            'too short' => ['+12345'],
            'has letters' => ['+55abc999999'],
            'has spaces' => ['+55 11 99999'],
            'empty' => [''],
        ];
    }

    #[Test]
    public function it_compares_equal_phones(): void
    {
        $a = Phone::fromString('+5511999999999');
        $b = Phone::fromString('+5511999999999');

        $this->assertTrue($a->equals($b));
    }

    #[Test]
    public function it_compares_different_phones(): void
    {
        $a = Phone::fromString('+5511999999999');
        $b = Phone::fromString('+5511888888888');

        $this->assertFalse($a->equals($b));
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $phone = Phone::fromString('+5511999999999');

        $this->assertSame('+5511999999999', (string) $phone);
    }
}
