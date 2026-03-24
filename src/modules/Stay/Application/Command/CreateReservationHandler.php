<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use DomainException;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\Service\GuestGateway;
use Modules\Stay\Domain\Specification\ReservationCreationSpecification;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;

final class CreateReservationHandler extends EventDispatchingHandler
{
    public function __construct(
        private ReservationRepository $repository,
        private ReservationCreationSpecification $specification,
        private GuestGateway $guestGateway,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(CreateReservation $command): ReservationId
    {
        $guestInfo = $this->guestGateway->findByUuid($command->guestId)
            ?? throw new DomainException("Guest '{$command->guestId}' not found.");

        $period = new ReservationPeriod($command->checkIn, $command->checkOut);

        if (! $this->specification->isSatisfiedBy($guestInfo->isVip, $period)) {
            throw new DomainException('Reservation cannot be created: policy check failed.');
        }

        $id = $this->repository->nextIdentity();
        $reservation = Reservation::create(
            $id,
            $command->guestId,
            $command->accountId,
            $command->stayId,
            $period,
            $command->adults,
            $command->children,
            $command->babies,
            $command->pets,
        );

        $this->repository->save($reservation);
        $this->dispatchEvents($reservation);

        return $id;
    }
}
