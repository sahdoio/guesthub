<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Handler;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Reservation\Application\Command\AddSpecialRequest;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;

final class AddSpecialRequestHandler
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function handle(AddSpecialRequest $command): SpecialRequestId
    {
        $id = ReservationId::fromString($command->reservationId);
        $reservation = $this->repository->findById($id)
            ?? throw ReservationNotFoundException::withId($id);

        $requestId = $reservation->addSpecialRequest(
            RequestType::from($command->requestType),
            $command->description,
        );

        $this->repository->save($reservation);

        foreach ($reservation->pullDomainEvents() as $event) {
            $this->dispatcher->dispatch($event);
        }

        return $requestId;
    }
}
