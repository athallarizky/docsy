<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // RBAC in FilePolicy
    }

    /**
     * Contract: metadata edit is title + department ONLY.
     * folder_id is intentionally absent — moving files is a different feature.
     *
     * @return array<string, array<int|string>>
     */
    public function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:255'],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id'),
            ],
        ];
    }
}
