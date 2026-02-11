<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Service;

interface TokenManager
{
    public function createToken(string $email, string $tokenName = 'api'): string;

    public function revokeAllTokens(string $email): void;
}
