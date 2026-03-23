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
    public function it_creates_money(): void
    {
        $money = new Money(1500, 'usd');

        $this->assertSame(1500, $money->amountInCents);
        $this->assertSame('usd', $money->currency);
    }

    #[Test]
    public function it_adds_money(): void
    {
        $a = new Money(100, 'usd');
        $b = new Money(200, 'usd');

        $result = $a->add($b);

        $this->assertSame(300, $result->amountInCents);
        $this->assertSame('usd', $result->currency);
    }

    #[Test]
    public function it_subtracts_money(): void
    {
        $a = new Money(500, 'usd');
        $b = new Money(200, 'usd');

        $result = $a->subtract($b);

        $this->assertSame(300, $result->amountInCents);
        $this->assertSame('usd', $result->currency);
    }

    #[Test]
    public function it_multiplies_money(): void
    {
        $money = new Money(100, 'usd');

        $result = $money->multiply(3);

        $this->assertSame(300, $result->amountInCents);
        $this->assertSame('usd', $result->currency);
    }

    #[Test]
    public function it_checks_zero(): void
    {
        $zero = new Money(0, 'usd');
        $nonZero = new Money(100, 'usd');

        $this->assertTrue($zero->isZero());
        $this->assertFalse($nonZero->isZero());
    }

    #[Test]
    public function it_checks_positive(): void
    {
        $positive = new Money(100, 'usd');
        $zero = new Money(0, 'usd');
        $negative = new Money(-100, 'usd');

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($zero->isPositive());
        $this->assertFalse($negative->isPositive());
    }

    #[Test]
    public function it_formats_money(): void
    {
        $money = new Money(12550, 'usd');

        $this->assertSame('USD 125.50', $money->format());
    }

    #[Test]
    public function it_checks_equality(): void
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
    public function it_rejects_different_currencies(): void
    {
        $usd = new Money(100, 'usd');
        $eur = new Money(100, 'eur');

        $this->expectException(InvalidArgumentException::class);
        $usd->add($eur);
    }

    #[Test]
    public function it_rejects_different_currencies_on_subtract(): void
    {
        $usd = new Money(100, 'usd');
        $eur = new Money(100, 'eur');

        $this->expectException(InvalidArgumentException::class);
        $usd->subtract($eur);
    }
}
