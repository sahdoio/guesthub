<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Exception;

final class MaxSpecialRequestsExceededException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Maximum number of special requests (5) has been reached.');
    }
}
