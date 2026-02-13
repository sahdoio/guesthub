<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain;

use DateTimeImmutable;
use DomainException;
use Modules\Reservation\Domain\Event\GuestCheckedIn;
use Modules\Reservation\Domain\Event\GuestCheckedOut;
use Modules\Reservation\Domain\Event\ReservationCancelled;
use Modules\Reservation\Domain\Event\ReservationConfirmed;
use Modules\Reservation\Domain\Event\ReservationCreated;
use Modules\Reservation\Domain\Event\SpecialRequestAdded;
use Modules\Reservation\Domain\Event\SpecialRequestFulfilled;
use Modules\Reservation\Domain\Exception\InvalidReservationStateException;
use Modules\Reservation\Domain\Exception\MaxSpecialRequestsExceededException;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Reservation\Domain\ValueObject\ReservationStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(Reservation::class)]
final class ReservationTest extends TestCase
{
    private function createReservation(): Reservation
    {
        return Reservation::create(
            ReservationId::generate(),
            Uuid::uuid7()->toString(),
            new ReservationPeriod(new DateTimeImmutable('+1 day'), new DateTimeImmutable('+4 days')),
            'DOUBLE',
        );
    }

    // --- State Machine ---

    #[Test]
    public function itCreatesWithPendingStatus(): void
    {
        $reservation = $this->createReservation();

        $this->assertSame(ReservationStatus::PENDING, $reservation->status);
        $this->assertSame('DOUBLE', $reservation->roomType);
        $this->assertNull($reservation->assignedRoomNumber);
    }

    #[Test]
    public function itRecordsCreatedEvent(): void
    {
        $reservation = $this->createReservation();

        $events = $reservation->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ReservationCreated::class, $events[0]);
    }

    #[Test]
    public function itConfirmsAPendingReservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->pullDomainEvents();

        $reservation->confirm();

        $this->assertSame(ReservationStatus::CONFIRMED, $reservation->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $reservation->confirmedAt);

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ReservationConfirmed::class, $events[0]);
    }

    #[Test]
    public function itChecksInAConfirmedReservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();
        $reservation->pullDomainEvents();

        $reservation->checkIn('201');

        $this->assertSame(ReservationStatus::CHECKED_IN, $reservation->status);
        $this->assertSame('201', $reservation->assignedRoomNumber);
        $this->assertInstanceOf(DateTimeImmutable::class, $reservation->checkedInAt);

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(GuestCheckedIn::class, $events[0]);
    }

    #[Test]
    public function itChecksOutACheckedInReservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();
        $reservation->checkIn('201');
        $reservation->pullDomainEvents();

        $reservation->checkOut();

        $this->assertSame(ReservationStatus::CHECKED_OUT, $reservation->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $reservation->checkedOutAt);

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(GuestCheckedOut::class, $events[0]);
    }

    #[Test]
    public function itCompletesFullLifecycle(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();
        $reservation->checkIn('305');
        $reservation->checkOut();

        $this->assertSame(ReservationStatus::CHECKED_OUT, $reservation->status);

        $events = $reservation->pullDomainEvents();
        $this->assertCount(4, $events);
        $this->assertInstanceOf(ReservationCreated::class, $events[0]);
        $this->assertInstanceOf(ReservationConfirmed::class, $events[1]);
        $this->assertInstanceOf(GuestCheckedIn::class, $events[2]);
        $this->assertInstanceOf(GuestCheckedOut::class, $events[3]);
    }

    // --- Cancellation ---

    #[Test]
    public function itCancelsAPendingReservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->pullDomainEvents();

        $reservation->cancel('Guest changed plans');

        $this->assertSame(ReservationStatus::CANCELLED, $reservation->status);
        $this->assertSame('Guest changed plans', $reservation->cancellationReason);
        $this->assertInstanceOf(DateTimeImmutable::class, $reservation->cancelledAt);

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ReservationCancelled::class, $events[0]);
    }

    #[Test]
    public function itCancelsAConfirmedReservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();

        $reservation->cancel('Emergency');

        $this->assertSame(ReservationStatus::CANCELLED, $reservation->status);
    }

    #[Test]
    public function itCannotCancelAfterCheckin(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();
        $reservation->checkIn('201');

        $this->expectException(InvalidReservationStateException::class);
        $reservation->cancel('Too late');
    }

    // --- Invalid Transitions ---

    #[Test]
    public function itCannotConfirmACancelledReservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->cancel('Reason');

        $this->expectException(InvalidReservationStateException::class);
        $reservation->confirm();
    }

    #[Test]
    public function itCannotCheckinAPendingReservation(): void
    {
        $reservation = $this->createReservation();

        $this->expectException(InvalidReservationStateException::class);
        $reservation->checkIn('201');
    }

    #[Test]
    public function itCannotCheckoutAConfirmedReservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();

        $this->expectException(InvalidReservationStateException::class);
        $reservation->checkOut();
    }

    // --- Special Requests ---

    #[Test]
    public function itAddsSpecialRequests(): void
    {
        $reservation = $this->createReservation();
        $reservation->pullDomainEvents();

        $requestId = $reservation->addSpecialRequest(RequestType::EARLY_CHECK_IN, 'Arrive at 10am');

        $this->assertCount(1, $reservation->specialRequests);
        $this->assertNotNull($requestId);

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(SpecialRequestAdded::class, $events[0]);
    }

    #[Test]
    public function itFulfillsASpecialRequest(): void
    {
        $reservation = $this->createReservation();
        $requestId = $reservation->addSpecialRequest(RequestType::EXTRA_BED, 'One extra bed');
        $reservation->pullDomainEvents();

        $reservation->fulfillSpecialRequest($requestId);

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(SpecialRequestFulfilled::class, $events[0]);
    }

    #[Test]
    public function itRemovesASpecialRequestWhenPending(): void
    {
        $reservation = $this->createReservation();
        $requestId = $reservation->addSpecialRequest(RequestType::EXTRA_BED, 'One extra bed');

        $reservation->removeSpecialRequest($requestId);

        $this->assertCount(0, $reservation->specialRequests);
    }

    #[Test]
    public function itCannotRemoveSpecialRequestAfterConfirmation(): void
    {
        $reservation = $this->createReservation();
        $requestId = $reservation->addSpecialRequest(RequestType::EXTRA_BED, 'Bed');
        $reservation->confirm();

        $this->expectException(DomainException::class);
        $reservation->removeSpecialRequest($requestId);
    }

    #[Test]
    public function itEnforcesMaxSpecialRequests(): void
    {
        $reservation = $this->createReservation();

        for ($i = 0; $i < 5; $i++) {
            $reservation->addSpecialRequest(RequestType::OTHER, "Request {$i}");
        }

        $this->expectException(MaxSpecialRequestsExceededException::class);
        $reservation->addSpecialRequest(RequestType::OTHER, 'One too many');
    }

    #[Test]
    public function itCannotAddSpecialRequestsAfterCheckout(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();
        $reservation->checkIn('201');
        $reservation->checkOut();

        $this->expectException(DomainException::class);
        $reservation->addSpecialRequest(RequestType::OTHER, 'Too late');
    }

    // --- Guest Profile ---

    #[Test]
    public function itStoresGuestProfileId(): void
    {
        $guestProfileId = Uuid::uuid7()->toString();
        $reservation = Reservation::create(
            ReservationId::generate(),
            $guestProfileId,
            new ReservationPeriod(new DateTimeImmutable('+1 day'), new DateTimeImmutable('+4 days')),
            'SUITE',
        );

        $this->assertSame($guestProfileId, $reservation->guestProfileId);
    }
}
