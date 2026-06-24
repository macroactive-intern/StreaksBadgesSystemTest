<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateStreakConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'creator_app_id'         => ['required', 'integer', 'min:1'],
            'streak_type'            => ['required', 'string'],
            'enabled'                => ['sometimes', 'boolean'],
            'qualifying_event_type'  => ['sometimes', 'nullable', 'string', new Enum(EventType::class)],
            'freeze_grants_per_month' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'at_risk_grace_hours'    => ['sometimes', 'integer', 'min:1', 'max:72'],
            'reward_config'          => ['sometimes', 'nullable', 'array'],
        ];
    }
}
