<?php

namespace App\Exceptions;

use RuntimeException;

class NoAvailableFreezeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No available streak freeze for this user and streak type.');
    }
}
