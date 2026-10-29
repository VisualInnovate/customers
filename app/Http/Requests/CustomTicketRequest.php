<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'type' => 'required',
            'service' => 'required|string|max:255',
            'status' => 'required|in:open,closed,pending',
            'priority' => 'required|in:low,medium,high,urgent',
            'agent' => 'nullable|string|max:255',
            'description' => 'required|max:5000',
        ];
    }
}
