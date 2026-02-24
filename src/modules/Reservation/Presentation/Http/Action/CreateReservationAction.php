<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use DateMalformedStringException;
use DateTimeImmutable;
use Modules\Reservation\Application\Command\CreateReservation;
use Modules\Reservation\Application\Command\CreateReservationHandler;
use Modules\Reservation\Application\Query\GetReservation;
use Modules\Reservation\Application\Query\GetReservationHandler;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateReservationAction
{
    public function __construct(
        private CreateReservationHandler $handler,
        private GetReservationHandler $queryHandler,
        private InputValidator $validator,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'guest_profile_id' => ['required', 'uuid'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type' => ['required', 'string', 'in:SINGLE,DOUBLE,SUITE'],
        ]);

        $id = $this->handler->handle(new CreateReservation(
            guestProfileId: $data['guest_profile_id'],
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
            roomType: $data['room_type'],
        ));

        $readModel = $this->queryHandler->handle(new GetReservation((string) $id));

        return JsonResponder::created(['data' => $readModel]);
    }
}
