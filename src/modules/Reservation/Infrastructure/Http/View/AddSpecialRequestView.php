<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Reservation\Application\Command\AddSpecialRequest;
use Modules\Reservation\Application\Command\AddSpecialRequestHandler;

final readonly class AddSpecialRequestView
{
    public function __construct(
        private AddSpecialRequestHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:early_check_in,late_check_out,extra_bed,dietary_restriction,special_occasion,other'],
            'description' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $this->handler->handle(new AddSpecialRequest(
            reservationId: $id,
            requestType: $data['type'],
            description: $data['description'],
        ));

        return redirect("/reservations/{$id}")->with('success', 'Special request added.');
    }
}
