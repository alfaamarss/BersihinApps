<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MenuUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'nama_menu' => ['sometimes','string','max:255'],
            'url' => ['nullable','string','max:255'],
            'icon' => ['nullable','string','max:255'],
            'id_html' => ['nullable','string','max:255'],
            'parent_id' => ['nullable','string','max:255'],
            'urutan' => ['nullable','string','max:255'],
            'tipe_menu' => ['nullable','in:be,fe'],
        ];
    }
}
