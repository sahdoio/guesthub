<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Application\Query\ListUsers;
use Modules\IAM\Application\Query\ListUsersHandler;
use Modules\IAM\Presentation\Http\Presenter\UserPresenter;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class UserListView
{
    public function __construct(
        private ListUsersHandler $handler,
        private TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): Response
    {
        $filters = [
            'search' => $request->query('search'),
            'loyalty_tier' => $request->query('loyalty_tier'),
        ];

        // For owners, scope guests to those who made reservations at their stays
        $user = $request->user();
        $user->load('types');
        $typeNames = $user->types->pluck('name')->toArray();

        if (in_array('owner', $typeNames, true) && ! in_array('superadmin', $typeNames, true)) {
            $guestUuids = DB::table('stay_guests')
                ->where('account_id', $this->tenantContext->id())
                ->pluck('guest_uuid')
                ->all();

            $filters['guest_uuids'] = $guestUuids;
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
