<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formats a BadgeDefinition model as an unearned / locked badge.
 * Shown in the badge display section when show_locked=true.
 * Intentionally omits rule_config to avoid leaking earning criteria to the client.
 */
class LockedBadgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'badge_id'       => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'icon'           => $this->icon,
            'badge_category' => $this->badge_category,
        ];
    }
}
