<?php

namespace App\Exceptions;

use RuntimeException;

class BackfillNotAllowedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Events older than the allowed backfill window cannot be recorded without admin approval.');
    }
}
