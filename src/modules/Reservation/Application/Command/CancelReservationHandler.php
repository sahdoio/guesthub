<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Command;

use Modules\Shared\Application\EventDispatcher;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Repository\ReservationRepository;

final class CancelReservationHandler
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly EventDispatcher $dispatcher,
    ) {}

    public function handle(CancelReservation $command): void
    {
        $id = ReservationId::fromString($command->reservationId);
        $reservation = $this->repository->findByUuid($id)
            ?? throw ReservationNotFoundException::withId($id);

        $reservation->cancel($command->reason);

        $this->repository->save($reservation);

        foreach ($reservation->pullDomainEvents() as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
}
