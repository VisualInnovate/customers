<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedReply extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller handles authorization/ownership
    }

    public function rules(): array
    {
        $rules = [
            'organization_id' => 'required|exists:organizations,id',
            'reply' => 'required|string|max:1000',
            // 'path' may be a string (existing URL) or an uploaded file — file validation performed in controller when a file is present
            'path' => 'nullable|string|max:1000',
        ];

        // allow user_id for creating on behalf of others (controller will gate this)
        if ($this->has('user_id')) {
            $rules['user_id'] = 'nullable|exists:users,id';
        }

        return $rules;
    }
}
