<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Modules\IAM\Domain\Service\EmailUniquenessChecker;

final readonly class EloquentEmailUniquenessChecker implements EmailUniquenessChecker
{
    public function __construct(
        private ActorModel $model,
    ) {}

    public function isEmailTaken(string $email): bool
    {
        return $this->model->newQuery()
            ->where('email', $email)
            ->exists();
    }
}
