<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Action;

use Modules\IAM\Application\Command\RevokeToken;
use Modules\IAM\Application\Command\RevokeTokenHandler;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LogoutAction
{
    public function __construct(
        private RevokeTokenHandler $handler,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');

        $this->handler->handle(new RevokeToken(
            actorEmail: $user->email,
        ));

        return JsonResponder::ok(['message' => 'Logged out.']);
    }
}
