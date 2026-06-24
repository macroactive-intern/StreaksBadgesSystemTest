<?php

namespace App\Enums;

enum ModerationStatus: string
{
    case Pending   = 'pending';
    case Resolved  = 'resolved';
    case Dismissed = 'dismissed';
}
