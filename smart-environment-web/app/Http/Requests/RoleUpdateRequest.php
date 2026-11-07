<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('role');
        return [
            'name' => ['sometimes','string','max:255','unique:roles,name,'.$id],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];
    }
}
