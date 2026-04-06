<?php

declare(strict_types=1);

namespace Tests\Unit\Billing\Domain\ValueObject;

use InvalidArgumentException;
use Modules\Billing\Domain\ValueObject\Money;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Money::class)]
final class MoneyTest extends TestCase
{
    #[Test]
    public function itCreatesMoney(): void
    {
        $money = new Money(1500, 'usd');

        $this->assertSame(1500, $money->amountInCents);
        $this->assertSame('usd', $money->currency);
    }

    #[Test]
    public function itAddsMoney(): void
    {
        $a = new Money(100, 'usd');
        $b = new Money(200, 'usd');

        $result = $a->add($b);

        $this->assertSame(300, $result->amountInCents);
        $this->assertSame('usd', $result->currency);
    }

    #[Test]
    public function itSubtractsMoney(): void
    {
        $a = new Money(500, 'usd');
        $b = new Money(200, 'usd');

        $result = $a->subtract($b);

        $this->assertSame(300, $result->amountInCents);
        $this->assertSame('usd', $result->currency);
    }

    #[Test]
    public function itMultipliesMoney(): void
    {
        $money = new Money(100, 'usd');

        $result = $money->multiply(3);

        $this->assertSame(300, $result->amountInCents);
        $this->assertSame('usd', $result->currency);
    }

    #[Test]
    public function itChecksZero(): void
    {
        $zero = new Money(0, 'usd');
        $nonZero = new Money(100, 'usd');

        $this->assertTrue($zero->isZero());
        $this->assertFalse($nonZero->isZero());
    }

    #[Test]
    public function itChecksPositive(): void
    {
        $positive = new Money(100, 'usd');
        $zero = new Money(0, 'usd');
        $negative = new Money(-100, 'usd');

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($zero->isPositive());
        $this->assertFalse($negative->isPositive());
    }

    #[Test]
    public function itFormatsMoney(): void
    {
        $money = new Money(12550, 'usd');

        $this->assertSame('USD 125.50', $money->format());
    }

    #[Test]
    public function itChecksEquality(): void
    {
        $a = new Money(100, 'usd');
        $b = new Money(100, 'usd');
        $c = new Money(200, 'usd');
        $d = new Money(100, 'eur');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
        $this->assertFalse($a->equals($d));
    }

    #[Test]
    public function itRejectsDifferentCurrencies(): void
    {
        $usd = new Money(100, 'usd');
        $eur = new Money(100, 'eur');

        $this->expectException(InvalidArgumentException::class);
        $usd->add($eur);
    }

    #[Test]
    public function itRejectsDifferentCurrenciesOnSubtract(): void
    {
        $usd = new Money(100, 'usd');
        $eur = new Money(100, 'eur');

        $this->expectException(InvalidArgumentException::class);
        $usd->subtract($eur);
    }
}
