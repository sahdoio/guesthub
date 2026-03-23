<?php

declare(strict_types=1);

namespace Modules\Stay\Presentation\Http\Action;

use Modules\Stay\Application\Command\CheckOutGuest;
use Modules\Stay\Application\Command\CheckOutGuestHandler;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CheckOutAction
{
    public function __construct(
        private CheckOutGuestHandler $handler,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->handler->handle(new CheckOutGuest(
            reservationId: $request->getAttribute('id'),
        ));

        return $this->responder->ok(['message' => 'Guest checked out.']);
    }
}
