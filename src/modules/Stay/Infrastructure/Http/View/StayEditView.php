<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;
use Modules\Stay\Presentation\Http\Presenter\StayPresenter;

final class StayEditView
{
    public function __construct(
        private StayRepository $stayRepository,
        private StayPresenter $stayPresenter,
    ) {}

    public function __invoke(Request $request, string $slug): Response
    {
        $stay = $this->stayRepository->findBySlug($slug);

        abort_if($stay === null, 404);

        return Inertia::render('Stays/Edit', [
            'stay' => $this->stayPresenter->toArray($stay),
            'types' => array_column(StayType::cases(), 'value'),
            'categories' => array_column(StayCategory::cases(), 'value'),
        ]);
    }
}
