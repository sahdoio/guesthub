<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Application\Query\ListUsers;
use Modules\IAM\Application\Query\ListUsersHandler;
use Modules\IAM\Domain\Repository\AccountGuestRepository;
use Modules\IAM\Presentation\Http\Presenter\UserPresenter;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class UserListView
{
    public function __construct(
        private ListUsersHandler $handler,
        private AccountGuestRepository $accountGuestRepository,
        private TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): Response
    {
        $filters = [
            'search' => $request->query('search'),
            'loyalty_tier' => $request->query('loyalty_tier'),
        ];

        // For owners, scope guests to those associated with their account
        $user = $request->user();
        $user->load('types');
        $typeNames = $user->types->pluck('name')->toArray();

        if (in_array('owner', $typeNames, true) && ! in_array('superadmin', $typeNames, true)) {
            $filters['guest_uuids'] = $this->accountGuestRepository->guestUuidsForAccount(
                $this->tenantContext->accountUuid(),
            );
        }

        $result = $this->handler->handle(
            new ListUsers(filters: array_filter($filters, fn ($v) => $v !== null && $v !== '')),
            new Pagination(
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 15),
            ),
        );

        return Inertia::render('Guests/Index', [
            'guests' => array_map(
                fn ($user) => UserPresenter::fromDomain($user),
                $result->items,
            ),
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
            ],
            'filters' => [
                'search' => $request->query('search', ''),
                'loyalty_tier' => $request->query('loyalty_tier', ''),
            ],
        ]);
    }
}
