<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;

final class SuperadminHomeView
{
    public function __construct(
        private AccountRepository $accountRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $accounts = array_map(function ($account) {
            $numericId = $this->accountRepository->resolveNumericId($account->uuid);

            $actors = ActorModel::where('account_id', $numericId)
                ->with('types')
                ->get()
                ->map(fn (ActorModel $actor) => [
                    'id' => $actor->id,
                    'name' => $actor->name,
                    'email' => $actor->email,
                    'roles' => $actor->types->pluck('name')->values()->toArray(),
                ])
                ->all();

            return [
                'uuid' => (string) $account->uuid,
                'name' => $account->name,
                'slug' => $account->slug ?? '',
                'actors' => $actors,
            ];
        }, $this->accountRepository->findAll());

        return Inertia::render('Superadmin/Home', [
            'accounts' => $accounts,
        ]);
    }
}
