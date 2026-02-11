<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Integration;

use Modules\IAM\Domain\Service\TokenManager;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;

final class SanctumTokenManager implements TokenManager
{
    public function createToken(string $email, string $tokenName = 'api'): string
    {
        $model = ActorModel::where('email', $email)->firstOrFail();

        return $model->createToken($tokenName)->plainTextToken;
    }

    public function revokeAllTokens(string $email): void
    {
        $model = ActorModel::where('email', $email)->firstOrFail();

        $model->tokens()->delete();
    }
}
