<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'guest_full_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
            'guest_document' => ['required', 'string', 'max:50'],
            'is_vip' => ['sometimes', 'boolean'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type' => ['required', 'string', 'in:SINGLE,DOUBLE,SUITE'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'guest_phone.regex' => 'Phone must be in E.164 format (e.g., +5511999999999).',
        ];
    }
}
