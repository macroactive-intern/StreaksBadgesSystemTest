<?php

namespace App\Exceptions;

use RuntimeException;

class FutureDatedEventException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Event timestamp resolves to a future local date and cannot be recorded for streak credit.');
    }
}
