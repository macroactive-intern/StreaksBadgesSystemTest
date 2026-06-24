<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RecordEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'              => ['required', 'integer', 'min:1'],
            'creator_app_id'       => ['required', 'integer', 'min:1'],
            'event_type'           => ['required', 'string', new Enum(EventType::class)],
            'event_timestamp_utc'  => ['required', 'date'],
            'user_timezone'        => ['required', 'string', 'timezone'],
            'metadata'             => ['sometimes', 'array'],
            'metadata.workout_id'  => ['sometimes', 'nullable', 'integer'],
            'metadata.meal_log_id' => ['sometimes', 'nullable', 'integer'],
            'metadata.habit_id'    => ['sometimes', 'nullable', 'integer'],
            'metadata.comment_post_id' => ['sometimes', 'nullable', 'integer'],
            'metadata.challenge_id'    => ['sometimes', 'nullable', 'integer'],
            'metadata.volume_lifted'   => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
