<?php

namespace App\Exceptions;

use RuntimeException;

class CommunityRateLimitException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Daily community event limit reached. Additional events will not count toward badge progress.');
    }
}
