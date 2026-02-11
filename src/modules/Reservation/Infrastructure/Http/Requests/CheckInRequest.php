<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'room_number' => ['required', 'string', 'regex:/^\d{1,4}[A-Za-z]?$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'room_number.regex' => 'Room number must be 1-4 digits optionally followed by a letter (e.g., 201, 101A).',
        ];
    }
}
