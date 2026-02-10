<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Handler;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Reservation\Application\Command\CreateReservation;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Service\ReservationPolicy;
use Modules\Reservation\Domain\ValueObject\Email;
use Modules\Reservation\Domain\ValueObject\Guest;
use Modules\Reservation\Domain\ValueObject\Phone;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;

final class CreateReservationHandler
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly ReservationPolicy $policy,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function handle(CreateReservation $command): ReservationId
    {
        $guest = Guest::create(
            $command->guestFullName,
            Email::fromString($command->guestEmail),
            Phone::fromString($command->guestPhone),
            $command->guestDocument,
            $command->isVip,
        );

        $period = new ReservationPeriod($command->checkIn, $command->checkOut);

        if (!$this->policy->canCreateReservation($guest, $period, $command->roomType)) {
            throw new \DomainException('Reservation cannot be created: policy check failed.');
        }

        $id = $this->repository->nextIdentity();
        $reservation = new Reservation($id, $guest, $period, $command->roomType);

        $this->repository->save($reservation);

        foreach ($reservation->pullDomainEvents() as $event) {
            $this->dispatcher->dispatch($event);
        }

        return $id;
    }
}
