<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\Reservation\Domain\Entity\SpecialRequest;
use Modules\Reservation\Domain\ValueObject\RequestStatus;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;
use Modules\Reservation\Infrastructure\Persistence\SpecialRequestReflector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpecialRequestReflector::class)]
final class SpecialRequestReflectorTest extends TestCase
{
    #[Test]
    public function itReconstructsAPendingSpecialRequest(): void
    {
        $id = SpecialRequestId::generate();
        $createdAt = new DateTimeImmutable('2026-01-15 10:00:00');

        $request = SpecialRequestReflector::reconstruct(
            id: $id,
            type: RequestType::EARLY_CHECK_IN,
            description: 'Arriving early morning',
            status: RequestStatus::PENDING,
            createdAt: $createdAt,
            fulfilledAt: null,
        );

        $this->assertInstanceOf(SpecialRequest::class, $request);
        $this->assertTrue($id->equals($request->id));
        $this->assertSame(RequestType::EARLY_CHECK_IN, $request->type);
        $this->assertSame('Arriving early morning', $request->description);
        $this->assertSame(RequestStatus::PENDING, $request->status);
        $this->assertSame($createdAt, $request->createdAt);
        $this->assertNull($request->fulfilledAt);
    }

    #[Test]
    public function itReconstructsAFulfilledSpecialRequest(): void
    {
        $fulfilledAt = new DateTimeImmutable('2026-01-16 14:00:00');

        $request = SpecialRequestReflector::reconstruct(
            id: SpecialRequestId::generate(),
            type: RequestType::EXTRA_BED,
            description: 'Extra bed for child',
            status: RequestStatus::FULFILLED,
            createdAt: new DateTimeImmutable('2026-01-15'),
            fulfilledAt: $fulfilledAt,
        );

        $this->assertSame(RequestStatus::FULFILLED, $request->status);
        $this->assertSame($fulfilledAt, $request->fulfilledAt);
    }
}
