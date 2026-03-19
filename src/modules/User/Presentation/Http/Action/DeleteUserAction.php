<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Action;

use Modules\User\Domain\Exception\UserNotFoundException;
use Modules\User\Domain\UserId;
use Modules\User\Domain\Repository\UserRepository;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteUserAction
{
    public function __construct(
        private UserRepository $repository,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        $user = $this->repository->findByUuid(UserId::fromString($uuid))
            ?? throw UserNotFoundException::withUuid($uuid);

        $this->repository->remove($user);

        return $this->responder->noContent();
    }
}
