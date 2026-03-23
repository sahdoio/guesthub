<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAM\Domain\Repository\TypeRepository;
use Modules\IAM\Domain\Type;
use Modules\IAM\Domain\ValueObject\TypeName;

class ActorTypeSeeder extends Seeder
{
    public function __construct(
        private readonly TypeRepository $repository,
    ) {}

    public function run(): void
    {
        foreach (TypeName::cases() as $typeName) {
            $existing = $this->repository->findByName($typeName);

            if ($existing !== null) {
                continue;
            }

            $type = Type::create(
                uuid: $this->repository->nextIdentity(),
                name: $typeName,
            );

            $this->repository->save($type);
        }
    }
}
