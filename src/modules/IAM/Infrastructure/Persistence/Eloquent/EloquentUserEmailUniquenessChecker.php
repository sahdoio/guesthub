<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Modules\IAM\Domain\Service\UserEmailUniquenessChecker;

final readonly class EloquentUserEmailUniquenessChecker implements UserEmailUniquenessChecker
{
    public function __construct(
        private UserModel $model,
    ) {}

    public function isEmailTaken(string $email): bool
    {
        return $this->model->newQuery()
            ->where('email', $email)
            ->exists();
    }
}
