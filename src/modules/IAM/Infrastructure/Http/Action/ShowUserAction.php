<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\Action;

use Modules\IAM\Domain\Exception\UserNotFoundException;
use Modules\IAM\Domain\UserId;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Infrastructure\Http\Presenter\UserPresenter;
use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowUserAction
{
    public function __construct(
        private UserRepository $repository,
        private AuthenticatedUserResolver $userResolver,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        $this->enforceOwnership($uuid);

        $user = $this->repository->findByUuid(UserId::fromString($uuid))
            ?? throw UserNotFoundException::withUuid($uuid);

        return $this->responder->ok(['data' => UserPresenter::fromDomain($user)]);
    }

    private function enforceOwnership(string $uuid): void
    {
        if ($this->userResolver->isOwnerOrSuperAdmin()) {
            return;
        }

        $ownUserUuid = $this->userResolver->resolveUserUuid();
        if ($ownUserUuid !== null && $ownUserUuid !== $uuid) {
            abort(403, 'Access denied.');
        }
    }
}
