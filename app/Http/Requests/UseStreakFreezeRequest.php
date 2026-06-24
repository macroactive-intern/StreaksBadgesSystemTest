<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UseStreakFreezeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'        => ['required', 'integer', 'min:1'],
            'creator_app_id' => ['required', 'integer', 'min:1'],
            'missed_date'    => ['required', 'date_format:Y-m-d'],
        ];
    }
}
