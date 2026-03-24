<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Modules\Stay\Application\Command\UpdateStay;
use Modules\Stay\Application\Command\UpdateStayHandler;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;

final class StayUpdateView
{
    public function __construct(
        private UpdateStayHandler $handler,
        private StayRepository $stayRepository,
    ) {}

    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        $stay = $this->stayRepository->findBySlug($slug);

        abort_if($stay === null, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', new Enum(StayType::class)],
            'category' => ['required', 'string', new Enum(StayCategory::class)],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'capacity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'regex:/^[1-9]\d{6,14}$/'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string'],
        ]);

        $newSlug = Str::slug($data['name']) !== Str::slug($stay->name)
            ? Str::slug($data['name']).'-'.Str::random(6)
            : $stay->slug;

        $this->handler->handle(new UpdateStay(
            stayId: (string) $stay->uuid,
            name: $data['name'],
            slug: $newSlug,
            type: StayType::from($data['type']),
            category: StayCategory::from($data['category']),
            pricePerNight: (float) $data['price_per_night'],
            capacity: (int) $data['capacity'],
            description: $data['description'] ?? null,
            address: $data['address'] ?? null,
            contactEmail: $data['contact_email'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
            amenities: $data['amenities'] ?? null,
        ));

        return redirect("/stays/{$newSlug}")->with('success', 'Stay updated.');
    }
}
