<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Infrastructure\Persistence\Eloquent\HotelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\RoomModel;

final class PortalDashboardView
{
    public function __invoke(Request $request): Response
    {
        $hotelRecords = HotelModel::query()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $hotels = $hotelRecords->map(function ($hotel) {
            $roomTypes = RoomModel::query()
                ->withoutGlobalScopes()
                ->where('hotel_id', $hotel->id)
                ->where('status', 'AVAILABLE')
                ->selectRaw('type, count(*) as available, min(price_per_night) as min_price')
                ->groupBy('type')
                ->get()
                ->map(fn ($row) => [
                    'type' => $row->type,
                    'available' => (int) $row->getAttribute('available'),
                    'min_price' => (float) $row->getAttribute('min_price'),
                ])
                ->all();

            $minPrice = null;
            $totalAvailable = 0;
            foreach ($roomTypes as $rt) {
                $totalAvailable += $rt['available'];
                if ($minPrice === null || $rt['min_price'] < $minPrice) {
                    $minPrice = $rt['min_price'];
                }
            }

            return [
                'uuid' => $hotel->uuid,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'description' => $hotel->description,
                'address' => $hotel->address,
                'contact_email' => $hotel->contact_email,
                'contact_phone' => $hotel->contact_phone,
                'min_price' => $minPrice,
                'available_rooms' => $totalAvailable,
                'room_types' => $roomTypes,
            ];
        })->all();

        return Inertia::render('Portal/Dashboard', [
            'hotels' => $hotels,
        ]);
    }
}
