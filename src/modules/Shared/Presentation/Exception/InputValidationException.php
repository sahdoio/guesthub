<?php

declare(strict_types=1);

namespace Modules\Shared\Presentation\Exception;

use RuntimeException;

final class InputValidationException extends RuntimeException
{
    /**
     * @param array<string, string[]> $errors
     */
    public function __construct(
        public readonly array $errors,
        string $message = 'The given data was invalid.',
    ) {
        parent::__construct($message);
    }
}
