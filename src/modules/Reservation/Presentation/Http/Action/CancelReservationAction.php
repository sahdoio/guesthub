<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use Modules\Reservation\Application\Command\CancelReservation;
use Modules\Reservation\Application\Command\CancelReservationHandler;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CancelReservationAction
{
    public function __construct(
        private CancelReservationHandler $handler,
        private InputValidator $validator,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'reason' => ['required', 'string', 'min:10'],
        ]);

        $this->handler->handle(new CancelReservation(
            reservationId: $request->getAttribute('id'),
            reason: $data['reason'],
        ));

        return JsonResponder::ok(['message' => 'Reservation cancelled.']);
    }
}
