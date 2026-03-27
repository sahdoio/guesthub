<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use Modules\IAM\Infrastructure\Integration\AccountApi;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;
use Modules\Stay\Domain\Exception\ReservationNotFoundException;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\ReservationId;

final class CheckOutGuestHandler extends EventDispatchingHandler
{
    public function __construct(
        private ReservationRepository $repository,
        private AccountApi $accountApi,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(CheckOutGuest $command): void
    {
        $id = ReservationId::fromString($command->reservationId);
        $reservation = $this->repository->findByUuid($id)
            ?? throw ReservationNotFoundException::withId($id);

        $reservation->checkOut();

        $this->repository->save($reservation, $this->accountApi->resolveNumericId($reservation->accountId));
        $this->dispatchEvents($reservation);
    }
}
