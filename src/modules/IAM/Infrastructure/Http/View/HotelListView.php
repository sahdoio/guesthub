<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\IAM\Presentation\Http\Presenter\HotelPresenter;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class HotelListView
{
    public function __construct(
        private HotelRepository $hotelRepository,
        private AccountRepository $accountRepository,
        private TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): Response
    {
        $account = $this->accountRepository->findByNumericId($this->tenantContext->id());

        $hotels = array_map(
            fn ($hotel) => HotelPresenter::fromDomain($hotel),
            $this->hotelRepository->findByAccountId($account->uuid),
        );

        return Inertia::render('Hotels/Index', [
            'hotels' => $hotels,
        ]);
    }
}
