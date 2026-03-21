<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Infrastructure\Persistence\Eloquent\HotelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\RoomModel;

final class PortalHotelListView
{
    public function __invoke(Request $request): Response
    {
        $q = $request->query('q', '');
        $checkIn = $request->query('check_in', '');
        $checkOut = $request->query('check_out', '');
        $guests = (int) $request->query('guests', 0);
        $page = (int) $request->query('page', 1);
        $perPage = 12;

        $query = HotelModel::query()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->orderBy('name');

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $hotels = collect($paginator->items())->map(function ($hotel) use ($guests) {
            $roomQuery = RoomModel::query()
                ->withoutGlobalScopes()
                ->where('hotel_id', $hotel->id)
                ->where('status', 'AVAILABLE');

            if ($guests > 0) {
                $roomQuery->where('capacity', '>=', $guests);
            }

            $roomTypes = $roomQuery
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

        return Inertia::render('Portal/Hotels/Index', [
            'hotels' => $hotels,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'q' => $q,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => $guests,
            ],
        ]);
    }
}
