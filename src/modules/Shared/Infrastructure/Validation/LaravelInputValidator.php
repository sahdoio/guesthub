<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Validation;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Modules\Shared\Presentation\Exception\InputValidationException;
use Modules\Shared\Presentation\Validation\InputValidator;

final readonly class LaravelInputValidator implements InputValidator
{
    public function __construct(
        private ValidationFactory $factory,
    ) {}

    public function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = $this->factory->make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new InputValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }
}
