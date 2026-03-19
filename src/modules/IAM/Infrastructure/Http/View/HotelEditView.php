<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\IAM\Presentation\Http\Presenter\HotelPresenter;

final class HotelEditView
{
    public function __construct(
        private HotelRepository $hotelRepository,
    ) {}

    public function __invoke(Request $request, string $slug): Response
    {
        $hotel = $this->hotelRepository->findBySlug($slug);

        abort_if($hotel === null, 404);

        return Inertia::render('Hotels/Edit', [
            'hotel' => HotelPresenter::fromDomain($hotel),
        ]);
    }
}
