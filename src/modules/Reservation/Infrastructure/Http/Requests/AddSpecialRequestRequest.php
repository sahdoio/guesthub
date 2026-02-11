<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Reservation\Domain\ValueObject\RequestType;

final class AddSpecialRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $validTypes = implode(',', array_column(RequestType::cases(), 'value'));

        return [
            'type' => ['required', 'string', "in:{$validTypes}"],
            'description' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
