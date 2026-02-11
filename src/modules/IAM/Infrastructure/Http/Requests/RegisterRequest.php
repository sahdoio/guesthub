<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{1,14}$/'],
            'document' => ['required', 'string', 'max:50'],
        ];
    }
}
