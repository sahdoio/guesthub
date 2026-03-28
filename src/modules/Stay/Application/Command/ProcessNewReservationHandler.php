<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use Modules\Shared\Application\EventDispatcher;
use Modules\Stay\Domain\Exception\ReservationNotFoundException;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Repository\StayGuestRepository;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\Service\GuestGateway;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationCreatedEvent;

final readonly class ProcessNewReservationHandler
{
    public function __construct(
        private ReservationRepository $repository,
        private StayGuestRepository $stayGuestRepository,
        private GuestGateway $guestGateway,
        private EventDispatcher $dispatcher,
    ) {}

    public function handle(ProcessNewReservation $command): void
    {
        $reservationId = ReservationId::fromString($command->reservationId);

        // Dispatch integration event for Billing module
        $reservation = $this->repository->findByUuid($reservationId)
            ?? throw ReservationNotFoundException::withId($reservationId);

        // Record in account_guests read model
        $this->stayGuestRepository->link($reservation->accountId, $reservation->guestId);

        $guestInfo = $this->guestGateway->findByUuid($reservation->guestId);

        $this->dispatcher->dispatch(new ReservationCreatedEvent(
            reservationId: $command->reservationId,
            guestEmail: $guestInfo->email ?? '',
            stayId: $reservation->stayId,
            checkIn: $reservation->period->checkIn->format('Y-m-d'),
            checkOut: $reservation->period->checkOut->format('Y-m-d'),
            isVip: $guestInfo->isVip ?? false,
        ));
    }
}
