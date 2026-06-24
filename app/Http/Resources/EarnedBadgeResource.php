<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formats a UserBadge model (with badgeDefinition eagerly loaded).
 * Used for badge display sections, profile, and community views.
 */
class EarnedBadgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_badge_id'  => $this->id,
            'badge_id'       => $this->badge_definition_id,
            'name'           => $this->badgeDefinition->name,
            'description'    => $this->badgeDefinition->description,
            'icon'           => $this->badgeDefinition->icon,
            'badge_category' => $this->badgeDefinition->badge_category,
            'earned_at'      => $this->earned_at->toIso8601String(),
            'awarded_by'     => $this->awarded_by, // null = automatically awarded
            'privacy_hidden' => (bool) $this->privacy_hidden,
            'is_featured'    => (bool) $this->is_featured,
        ];
    }
}
