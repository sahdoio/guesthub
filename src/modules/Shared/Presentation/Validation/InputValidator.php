<?php

declare(strict_types=1);

namespace Modules\Shared\Presentation\Validation;

use Modules\Shared\Presentation\Exception\InputValidationException;

interface InputValidator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, list<string>> $rules
     * @param array<string, string> $messages Custom error messages (e.g. ['phone.regex' => 'Must be E.164'])
     * @return array<string, mixed> Validated data
     *
     * @throws InputValidationException
     */
    public function validate(array $data, array $rules, array $messages = []): array;
}
