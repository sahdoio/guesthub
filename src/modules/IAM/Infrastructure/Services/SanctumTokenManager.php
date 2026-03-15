<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Services;

use Modules\IAM\Domain\Service\TokenManager;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;

final readonly class SanctumTokenManager implements TokenManager
{
    public function __construct(
        private ActorModel $model,
    ) {}

    public function createToken(string $email, string $tokenName = 'api'): string
    {
        $actor = $this->model->newQuery()->where('email', $email)->firstOrFail();

        return $actor->createToken($tokenName)->plainTextToken;
    }

    public function revokeAllTokens(string $email): void
    {
        $actor = $this->model->newQuery()->where('email', $email)->firstOrFail();

        $actor->tokens()->delete();
    }
}
