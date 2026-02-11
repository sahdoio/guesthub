<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Command;

use Modules\Reservation\Domain\Policies\ReservationPolicy;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Service\GuestGateway;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Shared\Application\EventDispatcher;

final class CreateReservationHandler
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly ReservationPolicy $policy,
        private readonly EventDispatcher $dispatcher,
        private readonly GuestGateway $guestGateway,
    ) {}

    public function handle(CreateReservation $command): ReservationId
    {
        $guestInfo = $this->guestGateway->findByUuid($command->guestProfileId)
            ?? throw new \DomainException("Guest profile '{$command->guestProfileId}' not found.");

        $period = new ReservationPeriod($command->checkIn, $command->checkOut);

        if (!$this->policy->canCreateReservation($guestInfo->isVip, $period, $command->roomType)) {
            throw new \DomainException('Reservation cannot be created: policy check failed.');
        }

        $id = $this->repository->nextIdentity();
        $reservation = Reservation::create($id, $command->guestProfileId, $period, $command->roomType);

        $this->repository->save($reservation);

        foreach ($reservation->pullDomainEvents() as $event) {
            $this->dispatcher->dispatch($event);
        }

        return $id;
    }
}
