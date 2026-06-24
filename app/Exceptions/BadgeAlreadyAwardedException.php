<?php

namespace App\Exceptions;

use RuntimeException;

class BadgeAlreadyAwardedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This badge has already been awarded to this user.');
    }
}
