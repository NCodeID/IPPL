<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'unique:tables,number,'.$this->route('table')],
            'capacity' => ['required', 'integer', 'min:1'],
            'is_available' => ['boolean'],
        ];
    }
}
