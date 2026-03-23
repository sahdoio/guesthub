<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;

final class PortalStayListView
{
    public function __invoke(Request $request): Response
    {
        $q = $request->query('q', '');
        $checkIn = $request->query('check_in', '');
        $checkOut = $request->query('check_out', '');
        $adults = (int) $request->query('adults', 1);
        $children = (int) $request->query('children', 0);
        $babies = (int) $request->query('babies', 0);
        $pets = (int) $request->query('pets', 0);
        $totalGuests = $adults + $children;
        $page = (int) $request->query('page', 1);
        $perPage = 12;

        $query = StayModel::query()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->orderBy('name');

        if ($q) {
            $lower = mb_strtolower($q);
            $query->where(function ($qb) use ($lower) {
                $qb->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(address) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$lower}%"]);
            });
        }

        if ($totalGuests > 1) {
            $query->where('capacity', '>=', $totalGuests);
        }

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $disk = Storage::disk(config('filesystems.stays_disk', 'public'));

        $stays = collect($paginator->items())->map(fn ($stay) => [
            'uuid' => $stay->uuid,
            'name' => $stay->name,
            'slug' => $stay->slug,
            'description' => $stay->description,
            'address' => $stay->address,
            'type' => $stay->type,
            'category' => $stay->category,
            'price_per_night' => (float) $stay->price_per_night,
            'capacity' => (int) $stay->capacity,
            'contact_email' => $stay->contact_email,
            'contact_phone' => $stay->contact_phone,
            'cover_image_url' => $stay->cover_image_path ? $disk->url($stay->cover_image_path) : null,
        ])->all();

        return Inertia::render('Portal/Stays/Index', [
            'stays' => $stays,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'q' => $q,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $adults,
                'children' => $children,
                'babies' => $babies,
                'pets' => $pets,
            ],
        ]);
    }
}
