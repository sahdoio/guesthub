<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;

final class StayCreateView
{
    public function __invoke(): Response
    {
        return Inertia::render('Stays/Create', [
            'types' => array_column(StayType::cases(), 'value'),
            'categories' => array_column(StayCategory::cases(), 'value'),
        ]);
    }
}
