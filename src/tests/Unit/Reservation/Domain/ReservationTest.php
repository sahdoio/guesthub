<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain;

use DateTimeImmutable;
use DomainException;
use Modules\Stay\Domain\Event\GuestCheckedIn;
use Modules\Stay\Domain\Event\GuestCheckedOut;
use Modules\Stay\Domain\Event\ReservationCancelled;
use Modules\Stay\Domain\Event\ReservationConfirmed;
use Modules\Stay\Domain\Event\ReservationCreated;
use Modules\Stay\Domain\Event\SpecialRequestAdded;
use Modules\Stay\Domain\Event\SpecialRequestFulfilled;
use Modules\Stay\Domain\Exception\InvalidReservationStateException;
use Modules\Stay\Domain\Exception\MaxSpecialRequestsExceededException;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\ValueObject\RequestType;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;
use Modules\Stay\Domain\ValueObject\ReservationStatus;
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
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            new ReservationPeriod(new DateTimeImmutable('+1 day'), new DateTimeImmutable('+4 days')),
        );
    }

    // --- State Machine ---

    #[Test]
    public function itCreatesWithPendingStatus(): void
    {
        $reservation = $this->createReservation();

        $this->assertSame(ReservationStatus::PENDING, $reservation->status);
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

        $reservation->checkIn();

        $this->assertSame(ReservationStatus::CHECKED_IN, $reservation->status);
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
        $reservation->checkIn();
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
        $reservation->checkIn();
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
        $reservation->checkIn();

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
        $reservation->checkIn();
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
        $reservation->checkIn();
        $reservation->checkOut();

        $this->expectException(DomainException::class);
        $reservation->addSpecialRequest(RequestType::OTHER, 'Too late');
    }

    // --- Guest Counts ---

    #[Test]
    public function itDefaultsToOneAdult(): void
    {
        $reservation = $this->createReservation();

        $this->assertSame(1, $reservation->adults);
        $this->assertSame(0, $reservation->children);
        $this->assertSame(0, $reservation->babies);
        $this->assertSame(0, $reservation->pets);
    }

    #[Test]
    public function itCreatesWithCustomGuestCounts(): void
    {
        $reservation = Reservation::create(
            ReservationId::generate(),
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            new ReservationPeriod(new DateTimeImmutable('+1 day'), new DateTimeImmutable('+4 days')),
            adults: 2,
            children: 3,
            babies: 1,
            pets: 2,
        );

        $this->assertSame(2, $reservation->adults);
        $this->assertSame(3, $reservation->children);
        $this->assertSame(1, $reservation->babies);
        $this->assertSame(2, $reservation->pets);
    }

    // --- Guest Profile ---

    #[Test]
    public function itStoresGuestId(): void
    {
        $guestId = Uuid::uuid7()->toString();
        $accountId = Uuid::uuid7()->toString();
        $stayId = Uuid::uuid7()->toString();
        $reservation = Reservation::create(
            ReservationId::generate(),
            $guestId,
            $accountId,
            $stayId,
            new ReservationPeriod(new DateTimeImmutable('+1 day'), new DateTimeImmutable('+4 days')),
        );

        $this->assertSame($guestId, $reservation->guestId);
    }
}
