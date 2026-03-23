<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\Action;

use Modules\IAM\Application\Command\UpdateUser;
use Modules\IAM\Application\Command\UpdateUserHandler;
use Modules\IAM\Domain\UserId;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Infrastructure\Http\Presenter\UserPresenter;
use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

        $this->enforceOwnership($uuid);

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
