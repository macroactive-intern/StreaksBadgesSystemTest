<?php

namespace App\Enums;

enum StreakStatus: string
{
    case Active = 'active';
    case AtRisk = 'at_risk';
    case Broken = 'broken';
}
