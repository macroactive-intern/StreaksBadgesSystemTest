<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal badge data for display next to a username in community / chat areas.
 * Only exposes name, icon, and category — enough to render an avatar badge
 * without overcrowding the UI.
 *
 * Returns null when the user has no earned badges (handled at the controller layer).
 *
 * Phase 1: all earned badges are treated as public/visible.
 */
class CommunityBadgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name'           => $this->badgeDefinition->name,
            'icon'           => $this->badgeDefinition->icon,
            'badge_category' => $this->badgeDefinition->badge_category,
        ];
    }
}
