<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Presentation\Http\Presenter\StayPresenter;

final class StayListView
{
    public function __construct(
        private StayRepository $stayRepository,
        private AccountRepository $accountRepository,
        private TenantContext $tenantContext,
        private StayPresenter $stayPresenter,
    ) {}

    public function __invoke(Request $request): Response
    {
        $account = $this->accountRepository->findByNumericId($this->tenantContext->id());

        $stays = array_map(
            fn ($stay) => $this->stayPresenter->toArray($stay),
            $this->stayRepository->findByAccountId($account->uuid),
        );

        return Inertia::render('Stays/Index', [
            'stays' => $stays,
        ]);
    }
}
