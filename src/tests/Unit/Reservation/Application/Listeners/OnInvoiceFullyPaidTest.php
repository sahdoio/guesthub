<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Application\Listeners;

use DateTimeImmutable;
use Modules\Shared\Application\EventDispatcher;
use Modules\Stay\Application\Command\ConfirmPaidReservation;
use Modules\Stay\Application\Command\ConfirmPaidReservationHandler;
use Modules\Stay\Domain\Event\ReservationConfirmed;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;
use Modules\Stay\Domain\ValueObject\ReservationStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(ConfirmPaidReservationHandler::class)]
final class OnInvoiceFullyPaidTest extends TestCase
{
    private function createPendingReservation(): Reservation
    {
        $reservation = Reservation::create(
            ReservationId::generate(),
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            new ReservationPeriod(new DateTimeImmutable('+10 days'), new DateTimeImmutable('+13 days')),
        );
        $reservation->pullDomainEvents();

        return $reservation;
    }

    #[Test]
    public function itConfirmsPendingReservationOnInvoiceFullyPaid(): void
    {
        $reservation = $this->createPendingReservation();
        $reservationId = (string) $reservation->uuid;

        $repository = $this->createMock(ReservationRepository::class);
        $repository->expects($this->once())
            ->method('findByUuidGlobal')
            ->willReturn($reservation);
        $repository->expects($this->once())
            ->method('save')
            ->with($reservation);

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ReservationConfirmed::class));

        $handler = new ConfirmPaidReservationHandler($repository, $dispatcher);

        $handler->handle(new ConfirmPaidReservation(
            reservationId: $reservationId,
        ));

        $this->assertSame(ReservationStatus::CONFIRMED, $reservation->status);
    }

    #[Test]
    public function itDoesNotConfirmAlreadyConfirmedReservation(): void
    {
        $reservation = $this->createPendingReservation();
        $reservation->confirm();
        $reservation->pullDomainEvents();

        $repository = $this->createMock(ReservationRepository::class);
        $repository->expects($this->once())
            ->method('findByUuidGlobal')
            ->willReturn($reservation);
        $repository->expects($this->never())->method('save');

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $handler = new ConfirmPaidReservationHandler($repository, $dispatcher);

        $handler->handle(new ConfirmPaidReservation(
            reservationId: (string) $reservation->uuid,
        ));
    }

    #[Test]
    public function itDoesNothingWhenReservationNotFound(): void
    {
        $repository = $this->createMock(ReservationRepository::class);
        $repository->expects($this->once())
            ->method('findByUuidGlobal')
            ->willReturn(null);
        $repository->expects($this->never())->method('save');

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $handler = new ConfirmPaidReservationHandler($repository, $dispatcher);

        $handler->handle(new ConfirmPaidReservation(
            reservationId: Uuid::uuid7()->toString(),
        ));
    }
}
