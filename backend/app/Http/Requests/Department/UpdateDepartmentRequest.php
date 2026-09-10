<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest {
  public function authorize(): bool {
    return true;
  }

  public function rules(): array {
    $departmentId = $this->route('department')?->id;

    return [
      'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($departmentId)],
      'description' => ['nullable', 'string', 'max:2000']
    ];
  }
}