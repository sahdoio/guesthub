<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateGuestProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
            'document' => ['required', 'string', 'max:50'],
            'loyalty_tier' => ['sometimes', 'string', 'in:bronze,silver,gold,platinum'],
            'preferences' => ['sometimes', 'array'],
            'preferences.*' => ['string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must be in E.164 format (e.g., +5511999999999).',
        ];
    }
}
