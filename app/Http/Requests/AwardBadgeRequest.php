<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AwardBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'creator_app_id'  => ['required', 'integer', 'min:1'],
            'badge_id'        => ['required', 'integer', 'min:1'],
            'awarded_by'      => ['required', 'integer', 'min:1'],
        ];
    }
}
