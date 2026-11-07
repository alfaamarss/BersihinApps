<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('user');
        return [
            'name' => ['sometimes','string','max:255'],
            'email' => ['sometimes','email','max:255','unique:users,email,'.$id],
            'password' => ['nullable','string','min:6'],
            'roles' => ['array'],
            'roles.*' => ['string'],
        ];
    }
}
