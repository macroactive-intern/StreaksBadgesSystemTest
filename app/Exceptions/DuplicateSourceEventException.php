<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicateSourceEventException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('An event for this source has already been recorded.');
    }
}
