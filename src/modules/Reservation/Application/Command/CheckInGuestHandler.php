<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Command;

use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

final readonly class CheckInGuestHandler extends EventDispatchingHandler
{
    public function __construct(
        private ReservationRepository $repository,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(CheckInGuest $command): void
    {
        $id = ReservationId::fromString($command->reservationId);
        $reservation = $this->repository->findByUuid($id)
            ?? throw ReservationNotFoundException::withId($id);

        $reservation->checkIn($command->roomNumber);

        $this->repository->save($reservation);
        $this->dispatchEvents($reservation);
    }
}
