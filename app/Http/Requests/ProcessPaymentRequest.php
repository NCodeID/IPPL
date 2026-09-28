<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:cash,qris'],
            'tendered_amount' => ['required_if:type,cash', 'numeric', 'min:0'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
        ];
    }
}
