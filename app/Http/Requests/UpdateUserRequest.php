<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$userId],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:admin,runner,kasir,dapur,gudang,akuntan'],
        ];
    }
}
