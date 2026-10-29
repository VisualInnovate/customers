<?php

namespace OrganizationModules\Shopify\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $organizationId  = session()->get('current_organization');
        return [
            'metadata' => 'required|array',
            'metadata.name' => [
                                'required',
                                'string',
                                'max:255',
                                // Rule::unique('organization_integrations')->where(function ($query) use ($organizationId) {
                                //     return $query->where('organization_id', $organizationId);
                                // }),
                                'in:shopify',
                            ],
            'metadata.shop_domain' => 'required',
            'metadata.access_token' => 'required|string',
            'metadata.active' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'metadata.required' => 'The metadata field is required.',
            'metadata.array' => 'The metadata must be a valid array.',
            'metadata.name.required' => 'The name field in metadata is required.',
            'metadata.name.string' => 'The name field must be a string.',
            'metadata.name.max' => 'The name field must not exceed 255 characters.',
            'metadata.name.in' => 'The name field must be either Shopify or WooCommerce.',
            'metadata.shop_domain.required' => 'The shop_domain is required.',
            'metadata.access_token.required' => 'The access_token is required.',
            'metadata.access_token.string' => 'The access_token must be a string.',
            'metadata.active.required' => 'The active field is required.',
            'metadata.active.boolean' => 'The active field must be true or false.',
        ];
    }


    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
