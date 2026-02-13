<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain;

use DateTimeImmutable;
use DomainException;
use Modules\Reservation\Domain\Entity\SpecialRequest;
use Modules\Reservation\Domain\ValueObject\RequestStatus;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpecialRequest::class)]
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
    public function itCreatesWithPendingStatus(): void
    {
        $request = $this->createRequest();

        $this->assertSame(RequestStatus::PENDING, $request->status);
        $this->assertSame(RequestType::EARLY_CHECK_IN, $request->type);
        $this->assertSame('Arrive at 10am', $request->description);
        $this->assertNull($request->fulfilledAt);
    }

    #[Test]
    public function itCanBeFulfilled(): void
    {
        $request = $this->createRequest();

        $request->fulfill();

        $this->assertSame(RequestStatus::FULFILLED, $request->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $request->fulfilledAt);
    }

    #[Test]
    public function itCannotBeFulfilledTwice(): void
    {
        $request = $this->createRequest();
        $request->fulfill();

        $this->expectException(DomainException::class);
        $request->fulfill();
    }

    #[Test]
    public function itCanBeCancelled(): void
    {
        $request = $this->createRequest();

        $request->cancel();

        $this->assertSame(RequestStatus::CANCELLED, $request->status);
    }

    #[Test]
    public function itCannotCancelAfterFulfillment(): void
    {
        $request = $this->createRequest();
        $request->fulfill();

        $this->expectException(DomainException::class);
        $request->cancel();
    }

    #[Test]
    public function itCannotFulfillAfterCancellation(): void
    {
        $request = $this->createRequest();
        $request->cancel();

        $this->expectException(DomainException::class);
        $request->fulfill();
    }

    #[Test]
    public function itChangesDescription(): void
    {
        $request = $this->createRequest();

        $request->changeDescription('Arrive at 8am instead');

        $this->assertSame('Arrive at 8am instead', $request->description);
    }

    #[Test]
    public function itRejectsEmptyDescription(): void
    {
        $request = $this->createRequest();

        $this->expectException(\InvalidArgumentException::class);
        $request->changeDescription('');
    }
}
