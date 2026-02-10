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
use Modules\Reservation\Domain\ValueObject\Email;
use Modules\Reservation\Domain\ValueObject\Guest;
use Modules\Reservation\Domain\ValueObject\Phone;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Reservation\Domain\ValueObject\ReservationStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReservationTest extends TestCase
{
    private function createReservation(): Reservation
    {
        return new Reservation(
            ReservationId::generate(),
            Guest::create('John Doe', Email::fromString('john@hotel.com'), Phone::fromString('+5511999999999'), '12345678900'),
            new ReservationPeriod(new DateTimeImmutable('+1 day'), new DateTimeImmutable('+4 days')),
            'DOUBLE',
        );
    }

    // --- State Machine ---

    #[Test]
    public function it_creates_with_pending_status(): void
    {
        $reservation = $this->createReservation();

        $this->assertSame(ReservationStatus::PENDING, $reservation->status());
        $this->assertSame('DOUBLE', $reservation->roomType());
        $this->assertNull($reservation->assignedRoomNumber());
    }

    #[Test]
    public function it_records_created_event(): void
    {
        $reservation = $this->createReservation();

        $events = $reservation->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ReservationCreated::class, $events[0]);
    }

    #[Test]
    public function it_confirms_a_pending_reservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->pullDomainEvents();

        $reservation->confirm();

        $this->assertSame(ReservationStatus::CONFIRMED, $reservation->status());
        $this->assertInstanceOf(DateTimeImmutable::class, $reservation->confirmedAt());

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ReservationConfirmed::class, $events[0]);
    }

    #[Test]
    public function it_checks_in_a_confirmed_reservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();
        $reservation->pullDomainEvents();

        $reservation->checkIn('201');

        $this->assertSame(ReservationStatus::CHECKED_IN, $reservation->status());
        $this->assertSame('201', $reservation->assignedRoomNumber());
        $this->assertInstanceOf(DateTimeImmutable::class, $reservation->checkedInAt());

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(GuestCheckedIn::class, $events[0]);
    }

    #[Test]
    public function it_checks_out_a_checked_in_reservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();
        $reservation->checkIn('201');
        $reservation->pullDomainEvents();

        $reservation->checkOut();

        $this->assertSame(ReservationStatus::CHECKED_OUT, $reservation->status());
        $this->assertInstanceOf(DateTimeImmutable::class, $reservation->checkedOutAt());

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(GuestCheckedOut::class, $events[0]);
    }

    #[Test]
    public function it_completes_full_lifecycle(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();
        $reservation->checkIn('305');
        $reservation->checkOut();

        $this->assertSame(ReservationStatus::CHECKED_OUT, $reservation->status());

        $events = $reservation->pullDomainEvents();
        $this->assertCount(4, $events);
        $this->assertInstanceOf(ReservationCreated::class, $events[0]);
        $this->assertInstanceOf(ReservationConfirmed::class, $events[1]);
        $this->assertInstanceOf(GuestCheckedIn::class, $events[2]);
        $this->assertInstanceOf(GuestCheckedOut::class, $events[3]);
    }

    // --- Cancellation ---

    #[Test]
    public function it_cancels_a_pending_reservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->pullDomainEvents();

        $reservation->cancel('Guest changed plans');

        $this->assertSame(ReservationStatus::CANCELLED, $reservation->status());
        $this->assertSame('Guest changed plans', $reservation->cancellationReason());
        $this->assertInstanceOf(DateTimeImmutable::class, $reservation->cancelledAt());

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ReservationCancelled::class, $events[0]);
    }

    #[Test]
    public function it_cancels_a_confirmed_reservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();

        $reservation->cancel('Emergency');

        $this->assertSame(ReservationStatus::CANCELLED, $reservation->status());
    }

    #[Test]
    public function it_cannot_cancel_after_checkin(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();
        $reservation->checkIn('201');

        $this->expectException(InvalidReservationStateException::class);
        $reservation->cancel('Too late');
    }

    // --- Invalid Transitions ---

    #[Test]
    public function it_cannot_confirm_a_cancelled_reservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->cancel('Reason');

        $this->expectException(InvalidReservationStateException::class);
        $reservation->confirm();
    }

    #[Test]
    public function it_cannot_checkin_a_pending_reservation(): void
    {
        $reservation = $this->createReservation();

        $this->expectException(InvalidReservationStateException::class);
        $reservation->checkIn('201');
    }

    #[Test]
    public function it_cannot_checkout_a_confirmed_reservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();

        $this->expectException(InvalidReservationStateException::class);
        $reservation->checkOut();
    }

    // --- Special Requests ---

    #[Test]
    public function it_adds_special_requests(): void
    {
        $reservation = $this->createReservation();
        $reservation->pullDomainEvents();

        $requestId = $reservation->addSpecialRequest(RequestType::EARLY_CHECK_IN, 'Arrive at 10am');

        $this->assertCount(1, $reservation->specialRequests());
        $this->assertNotNull($requestId);

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(SpecialRequestAdded::class, $events[0]);
    }

    #[Test]
    public function it_fulfills_a_special_request(): void
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
    public function it_removes_a_special_request_when_pending(): void
    {
        $reservation = $this->createReservation();
        $requestId = $reservation->addSpecialRequest(RequestType::EXTRA_BED, 'One extra bed');

        $reservation->removeSpecialRequest($requestId);

        $this->assertCount(0, $reservation->specialRequests());
    }

    #[Test]
    public function it_cannot_remove_special_request_after_confirmation(): void
    {
        $reservation = $this->createReservation();
        $requestId = $reservation->addSpecialRequest(RequestType::EXTRA_BED, 'Bed');
        $reservation->confirm();

        $this->expectException(DomainException::class);
        $reservation->removeSpecialRequest($requestId);
    }

    #[Test]
    public function it_enforces_max_special_requests(): void
    {
        $reservation = $this->createReservation();

        for ($i = 0; $i < 5; $i++) {
            $reservation->addSpecialRequest(RequestType::OTHER, "Request {$i}");
        }

        $this->expectException(MaxSpecialRequestsExceededException::class);
        $reservation->addSpecialRequest(RequestType::OTHER, 'One too many');
    }

    #[Test]
    public function it_cannot_add_special_requests_after_checkout(): void
    {
        $reservation = $this->createReservation();
        $reservation->confirm();
        $reservation->checkIn('201');
        $reservation->checkOut();

        $this->expectException(DomainException::class);
        $reservation->addSpecialRequest(RequestType::OTHER, 'Too late');
    }

    // --- Guest Contact ---

    #[Test]
    public function it_changes_guest_contact(): void
    {
        $reservation = $this->createReservation();

        $reservation->changeGuestContact(
            Email::fromString('newemail@hotel.com'),
            Phone::fromString('+5511888888888'),
        );

        $this->assertSame('newemail@hotel.com', $reservation->guest()->email->value);
        $this->assertSame('+5511888888888', $reservation->guest()->phone->value);
        $this->assertSame('John Doe', $reservation->guest()->fullName);
    }
}
