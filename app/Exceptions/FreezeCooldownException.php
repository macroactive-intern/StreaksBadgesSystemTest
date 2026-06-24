<?php

namespace App\Exceptions;

use RuntimeException;

class FreezeCooldownException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A streak freeze was already used within the last 30 days.');
    }
}
