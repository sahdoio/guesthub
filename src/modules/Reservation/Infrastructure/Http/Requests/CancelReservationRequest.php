<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CancelReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10'],
        ];
    }
}
