<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use Modules\Stay\Domain\Exception\ReservationNotFoundException;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\ReservationId;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

final class CancelReservationHandler extends EventDispatchingHandler
{
    public function __construct(
        private ReservationRepository $repository,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(CancelReservation $command): void
    {
        $id = ReservationId::fromString($command->reservationId);
        $reservation = $this->repository->findByUuid($id)
            ?? throw ReservationNotFoundException::withId($id);

        $reservation->cancel($command->reason);

        $this->repository->save($reservation);
        $this->dispatchEvents($reservation);
    }
}
