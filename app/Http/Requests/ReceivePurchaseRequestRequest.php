<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:purchase_request_items,id'],
            'items.*.actual_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.actual_price' => ['required', 'integer', 'min:0'],
        ];
    }
}
