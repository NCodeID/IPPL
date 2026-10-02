<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.estimated_quantity' => ['required', 'numeric', 'min:0.1'],
            'items.*.estimated_price' => ['required', 'integer', 'min:0'],
        ];
    }
}
