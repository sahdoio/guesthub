<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Handler;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Reservation\Application\Command\ConfirmReservation;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Repository\ReservationRepository;

final class ConfirmReservationHandler
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function handle(ConfirmReservation $command): void
    {
        $id = ReservationId::fromString($command->reservationId);
        $reservation = $this->repository->findById($id)
            ?? throw ReservationNotFoundException::withId($id);

        $reservation->confirm();

        $this->repository->save($reservation);

        foreach ($reservation->pullDomainEvents() as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
}
