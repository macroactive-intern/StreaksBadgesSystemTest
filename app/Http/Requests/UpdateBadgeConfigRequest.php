<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBadgeConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'creator_app_id' => ['required', 'integer', 'min:1'],
            'badge_id'       => ['required', 'integer', 'min:1'],
            'enabled'        => ['sometimes', 'boolean'],
            'reward'         => ['sometimes', 'nullable', 'array'],
        ];
    }
}
