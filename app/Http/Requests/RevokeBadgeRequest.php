<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevokeBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'creator_app_id' => ['required', 'integer', 'min:1'],
            'revoke_reason'  => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
