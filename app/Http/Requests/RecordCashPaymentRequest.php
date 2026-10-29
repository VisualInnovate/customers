<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordCashPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan' => [
                'required',
                'string',
                Rule::exists('subscription_plans', 'uuid')
                    ->where('status', 'active')
                    ->whereNull('deleted_at'),
            ],
            'reference' => ['nullable', 'string', 'max:255'],
            'cash_received' => ['accepted'],
        ];
    }
}
