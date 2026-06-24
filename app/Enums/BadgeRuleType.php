<?php

namespace App\Enums;

enum BadgeRuleType: string
{
    case Streak = 'streak';
    case Milestone = 'milestone';
    case Challenge = 'challenge';
    case Certification = 'certification';
    case Community = 'community';
}
