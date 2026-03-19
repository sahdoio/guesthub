<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class PortalHotelShowView
{
    public function __construct(
        private HotelRepository $hotelRepository,
        private AccountRepository $accountRepository,
        private RoomRepository $roomRepository,
        private TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request, string $slug): Response
    {
        $hotel = $this->hotelRepository->findBySlug($slug);

        if (! $hotel) {
            abort(404, 'Hotel not found.');
        }

        // Set tenant context to load this hotel's rooms
        $numericId = $this->accountRepository->resolveNumericId($hotel->accountId);
        $this->tenantContext->set($numericId);

        $roomTypes = $this->roomRepository->getAvailableRoomTypes();

        return Inertia::render('Portal/Hotels/Show', [
            'hotel' => [
                'uuid' => (string) $hotel->uuid,
                'account_uuid' => (string) $hotel->accountId,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'description' => $hotel->description,
                'address' => $hotel->address,
                'contact_email' => $hotel->contactEmail,
                'contact_phone' => $hotel->contactPhone,
            ],
            'roomTypes' => $roomTypes,
        ]);
    }
}
