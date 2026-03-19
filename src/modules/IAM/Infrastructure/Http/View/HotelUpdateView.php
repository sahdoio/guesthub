<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\IAM\Application\Command\UpdateHotel;
use Modules\IAM\Application\Command\UpdateHotelHandler;
use Modules\IAM\Domain\Repository\HotelRepository;

final class HotelUpdateView
{
    public function __construct(
        private UpdateHotelHandler $handler,
        private HotelRepository $hotelRepository,
    ) {}

    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        $hotel = $this->hotelRepository->findBySlug($slug);

        abort_if($hotel === null, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'regex:/^[1-9]\d{6,14}$/'],
        ]);

        $newSlug = Str::slug($data['name']) !== Str::slug($hotel->name)
            ? Str::slug($data['name']) . '-' . Str::random(6)
            : $hotel->slug;

        $this->handler->handle(new UpdateHotel(
            hotelId: (string) $hotel->uuid,
            name: $data['name'],
            slug: $newSlug,
            description: $data['description'] ?? null,
            address: $data['address'] ?? null,
            contactEmail: $data['contact_email'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
        ));

        return redirect("/hotels/{$newSlug}")->with('success', 'Hotel updated.');
    }
}
