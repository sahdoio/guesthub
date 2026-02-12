<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use Modules\IAM\Domain\Exception\ActorNotFoundException;
use Modules\IAM\Domain\Exception\InvalidCredentialsException;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\Service\TokenManager;

final readonly class AuthenticateActorHandler
{
    public function __construct(
        private ActorRepository $repository,
        private PasswordHasher $hasher,
        private TokenManager $tokenManager,
    ) {}

    /**
     * @return array{token: string, actor_id: string}
     */
    public function handle(AuthenticateActor $command): array
    {
        $actor = $this->repository->findByEmail($command->email)
            ?? throw ActorNotFoundException::withEmail($command->email);

        if (!$this->hasher->verify($command->password, $actor->password)) {
            throw new InvalidCredentialsException();
        }

        $token = $this->tokenManager->createToken($command->email);

        return [
            'token' => $token,
            'actor_id' => (string) $actor->uuid,
        ];
    }
}
