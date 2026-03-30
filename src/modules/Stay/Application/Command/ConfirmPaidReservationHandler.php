<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\ValueObject\ReservationStatus;

final class ConfirmPaidReservationHandler extends EventDispatchingHandler
{
    public function __construct(
        private readonly ReservationRepository $repository,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(ConfirmPaidReservation $command): void
    {
        $id = ReservationId::fromString($command->reservationId);
        $reservation = $this->repository->findByUuidGlobal($id);

        if ($reservation === null) {
            return;
        }

        if ($reservation->status !== ReservationStatus::PENDING) {
            return;
        }

        $reservation->confirm();

        $this->repository->save($reservation);
        $this->dispatchEvents($reservation);
    }
}
