<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Action;

use Modules\IAM\Application\Command\UpdateUser;
use Modules\IAM\Application\Command\UpdateUserHandler;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\ValueObject\UserId;
use Modules\IAM\Presentation\Http\Presenter\UserPresenter;
use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class UpdateUserAction
{
    public function __construct(
        private UpdateUserHandler $handler,
        private UserRepository $repository,
        private AuthenticatedUserResolver $userResolver,
        private InputValidator $validator,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        if (! $this->isAuthorized($uuid)) {
            return $this->responder->error(['message' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'regex:/^[1-9]\d{6,14}$/'],
            'loyalty_tier' => ['sometimes', 'string', 'in:bronze,silver,gold,platinum'],
            'preferences' => ['sometimes', 'array'],
            'preferences.*' => ['string', 'max:255'],
        ], [
            'phone.regex' => 'Phone must contain only digits (e.g., 5511999999999).',
        ]);

        $this->handler->handle(new UpdateUser(
            userId: $uuid,
            fullName: $data['full_name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            loyaltyTier: $data['loyalty_tier'] ?? null,
            preferences: $data['preferences'] ?? null,
        ));

        $user = $this->repository->findByUuid(UserId::fromString($uuid));

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
