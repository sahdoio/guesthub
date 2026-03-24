<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\ActorRepository;

final class SuperadminHomeView
{
    public function __construct(
        private AccountRepository $accountRepository,
        private ActorRepository $actorRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $accounts = array_map(function ($account) {
            $numericId = $this->accountRepository->resolveNumericId($account->uuid);

            $actors = $this->actorRepository->findActorsByAccountId($numericId);

            return [
                'uuid' => (string) $account->uuid,
                'name' => $account->name,
                'slug' => $account->slug ?? '',
                'actors' => array_map(fn (array $actor) => [
                    'id' => $actor['id'],
                    'name' => $actor['name'],
                    'email' => $actor['email'],
                    'roles' => $actor['type_names'],
                ], $actors),
            ];
        }, $this->accountRepository->findAll());

        return Inertia::render('Superadmin/Home', [
            'accounts' => $accounts,
        ]);
    }
}
