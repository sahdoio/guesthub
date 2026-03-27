<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Action;

use JsonException;
use Modules\IAM\Domain\Exception\UserNotFoundException;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\ValueObject\UserId;
use Modules\IAM\Presentation\Http\Presenter\UserPresenter;
use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class ShowUserAction
{
    public function __construct(
        private UserRepository $repository,
        private AuthenticatedUserResolver $userResolver,
        private JsonResponder $responder,
    ) {}

    /**
     * @throws JsonException
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        if (! $this->isAuthorized($uuid)) {
            return $this->responder->error(['message' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->repository->findByUuid(UserId::fromString($uuid))
            ?? throw UserNotFoundException::withUuid($uuid);

        return $this->responder->ok(['data' => UserPresenter::fromDomain($user)]);
    }

    private function isAuthorized(string $uuid): bool
    {
        if ($this->userResolver->isOwnerOrSuperAdmin()) {
            return true;
        }

        $ownUserUuid = $this->userResolver->resolveUserUuid();

        return $ownUserUuid === null || $ownUserUuid === $uuid;
    }
}
