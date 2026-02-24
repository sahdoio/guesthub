<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use Modules\Reservation\Application\Command\CheckOutGuest;
use Modules\Reservation\Application\Command\CheckOutGuestHandler;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CheckOutAction
{
    public function __construct(
        private CheckOutGuestHandler $handler,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->handler->handle(new CheckOutGuest(
            reservationId: $request->getAttribute('id'),
        ));

        return JsonResponder::ok(['message' => 'Guest checked out.']);
    }
}
