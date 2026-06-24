<?php

namespace App\Models;

use App\Enums\BadgeRuleType;
use Illuminate\Database\Eloquent\Model;

class BadgeDefinition extends Model
{
    protected $fillable = [
        'creator_app_id',
        'name',
        'description',
        'badge_category',
        'icon',
        'rule_type',
        'rule_config',
        'enabled',
    ];

    protected $casts = [
        'rule_type' => BadgeRuleType::class,
        'rule_config' => 'array',
        'enabled' => 'boolean',
    ];
}
