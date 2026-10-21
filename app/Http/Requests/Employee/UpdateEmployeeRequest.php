<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employee.edit');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->input('first_name', '')),
            'full_name' => trim(
                $this->input('first_name', '').' '.$this->input('last_name', '')
            ),
        ]);
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'dni' => ['required', 'string', 'max:15', Rule::unique('employees', 'dni')->ignore($employee)],
            'name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'full_name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('employees', 'email')->ignore($employee)],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:150'],
            'organizational_unit_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'is_technician' => ['boolean'],
            'specialty' => ['nullable', 'string', 'max:150'],
            'level' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }
}
