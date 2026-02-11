<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain;

use DateTimeImmutable;
use DomainException;
use Modules\Reservation\Domain\Entity\SpecialRequest;
use Modules\Reservation\Domain\ValueObject\RequestStatus;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SpecialRequestTest extends TestCase
{
    private function createRequest(
        RequestType $type = RequestType::EARLY_CHECK_IN,
        string $description = 'Arrive at 10am',
    ): SpecialRequest {
        return SpecialRequest::create(
            SpecialRequestId::generate(),
            $type,
            $description,
            new DateTimeImmutable(),
        );
    }

    #[Test]
    public function it_creates_with_pending_status(): void
    {
        $request = $this->createRequest();

        $this->assertSame(RequestStatus::PENDING, $request->status);
        $this->assertSame(RequestType::EARLY_CHECK_IN, $request->type);
        $this->assertSame('Arrive at 10am', $request->description);
        $this->assertNull($request->fulfilledAt);
    }

    #[Test]
    public function it_can_be_fulfilled(): void
    {
        $request = $this->createRequest();

        $request->fulfill();

        $this->assertSame(RequestStatus::FULFILLED, $request->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $request->fulfilledAt);
    }

    #[Test]
    public function it_cannot_be_fulfilled_twice(): void
    {
        $request = $this->createRequest();
        $request->fulfill();

        $this->expectException(DomainException::class);
        $request->fulfill();
    }

    #[Test]
    public function it_can_be_cancelled(): void
    {
        $request = $this->createRequest();

        $request->cancel();

        $this->assertSame(RequestStatus::CANCELLED, $request->status);
    }

    #[Test]
    public function it_cannot_cancel_after_fulfillment(): void
    {
        $request = $this->createRequest();
        $request->fulfill();

        $this->expectException(DomainException::class);
        $request->cancel();
    }

    #[Test]
    public function it_cannot_fulfill_after_cancellation(): void
    {
        $request = $this->createRequest();
        $request->cancel();

        $this->expectException(DomainException::class);
        $request->fulfill();
    }

    #[Test]
    public function it_changes_description(): void
    {
        $request = $this->createRequest();

        $request->changeDescription('Arrive at 8am instead');

        $this->assertSame('Arrive at 8am instead', $request->description);
    }

    #[Test]
    public function it_rejects_empty_description(): void
    {
        $request = $this->createRequest();

        $this->expectException(\InvalidArgumentException::class);
        $request->changeDescription('');
    }
}
