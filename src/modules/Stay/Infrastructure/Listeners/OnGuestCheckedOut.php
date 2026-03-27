<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Listeners;

use Modules\Shared\Application\EventDispatcher;
use Modules\Stay\Domain\Event\GuestCheckedOut;
use Modules\Stay\Domain\Exception\ReservationNotFoundException;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Service\GuestGateway;
use Modules\Stay\Infrastructure\IntegrationEvent\GuestCheckedOutEvent;

final readonly class OnGuestCheckedOut
{
    public function __construct(
        private ReservationRepository $repository,
        private EventDispatcher $dispatcher,
        private GuestGateway $guestGateway,
    ) {}

    public function handle(GuestCheckedOut $event): void
    {
        $reservation = $this->repository->findByUuid($event->reservationId)
            ?? throw ReservationNotFoundException::withId($event->reservationId);

        $guestInfo = $this->guestGateway->findByUuid($reservation->guestId);

        $this->dispatcher->dispatch(new GuestCheckedOutEvent(
            reservationId: (string) $event->reservationId,
            guestEmail: $guestInfo->email ?? '',
        ));
    }
}
