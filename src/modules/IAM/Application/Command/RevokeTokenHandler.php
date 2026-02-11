<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use Modules\IAM\Domain\Service\TokenManager;

final class RevokeTokenHandler
{
    public function __construct(
        private readonly TokenManager $tokenManager,
    ) {}

    public function handle(RevokeToken $command): void
    {
        $this->tokenManager->revokeAllTokens($command->actorEmail);
    }
}
