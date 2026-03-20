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

final class PortalDashboardView
{
    public function __construct(
        private HotelRepository $hotelRepository,
        private AccountRepository $accountRepository,
        private RoomRepository $roomRepository,
        private TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): Response
    {
        $allHotels = $this->hotelRepository->findAll();

        $hotels = array_map(function ($hotel) {
            // Set tenant context to load this hotel's rooms
            $numericId = $this->accountRepository->resolveNumericId($hotel->accountId);
            $this->tenantContext->set($numericId);

            $roomTypes = $this->roomRepository->getAvailableRoomTypes();
            $minPrice = null;
            $totalAvailable = 0;

            foreach ($roomTypes as $rt) {
                $totalAvailable += $rt['available'];
                if ($minPrice === null || $rt['min_price'] < $minPrice) {
                    $minPrice = $rt['min_price'];
                }
            }

            return [
                'uuid' => (string) $hotel->uuid,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'description' => $hotel->description,
                'address' => $hotel->address,
                'contact_email' => $hotel->contactEmail,
                'contact_phone' => $hotel->contactPhone,
                'min_price' => $minPrice,
                'available_rooms' => $totalAvailable,
                'room_types' => $roomTypes,
            ];
        }, $allHotels);

        return Inertia::render('Portal/Dashboard', [
            'hotels' => $hotels,
        ]);
    }
}
