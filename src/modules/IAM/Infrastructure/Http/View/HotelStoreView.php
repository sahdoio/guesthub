<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\IAM\Application\Command\CreateHotel;
use Modules\IAM\Application\Command\CreateHotelHandler;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class HotelStoreView
{
    public function __construct(
        private CreateHotelHandler $handler,
        private AccountRepository $accountRepository,
        private TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'regex:/^[1-9]\d{6,14}$/'],
        ]);

        $account = $this->accountRepository->findByNumericId($this->tenantContext->id());
        $slug = Str::slug($data['name']) . '-' . Str::random(6);

        $this->handler->handle(new CreateHotel(
            accountId: (string) $account->uuid,
            name: $data['name'],
            slug: $slug,
            description: $data['description'] ?? null,
            address: $data['address'] ?? null,
            contactEmail: $data['contact_email'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
        ));

        return redirect('/hotels')->with('success', 'Hotel created.');
    }
}
