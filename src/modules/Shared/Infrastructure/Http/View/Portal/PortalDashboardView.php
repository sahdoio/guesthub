<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;

final class PortalDashboardView
{
    public function __invoke(Request $request): Response
    {
        $stayRecords = StayModel::query()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $disk = Storage::disk(config('filesystems.stays_disk', 'public'));

        $stays = $stayRecords->map(fn ($stay) => [
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

        return Inertia::render('Portal/Dashboard', [
            'stays' => $stays,
        ]);
    }
}
