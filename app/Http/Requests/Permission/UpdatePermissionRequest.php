<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($this->route('permission'))],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del permiso es requerido.',
            'name.unique' => 'Ya existe un permiso con ese nombre.',
        ];
    }
}
