<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'ingredient_id' => ['required', 'exists:products,id', Rule::exists('products', 'id')->where('is_sellable', false)],
            'quantity_required' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
