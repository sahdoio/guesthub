<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Command;

use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

final readonly class AddSpecialRequestHandler extends EventDispatchingHandler
{
    public function __construct(
        private ReservationRepository $repository,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(AddSpecialRequest $command): SpecialRequestId
    {
        $id = ReservationId::fromString($command->reservationId);
        $reservation = $this->repository->findByUuid($id)
            ?? throw ReservationNotFoundException::withId($id);

        $requestId = $reservation->addSpecialRequest(
            RequestType::from($command->requestType),
            $command->description,
        );

        $this->repository->save($reservation);
        $this->dispatchEvents($reservation);

        return $requestId;
    }
}
