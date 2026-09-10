<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // RBAC in FilePolicy
    }

    /**
     * @return array<string, array<int|string>>
     */
    public function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:255'],
            // a soft-deleted folder must not accept new files (ghost-parent guard)
            'folder_id'     => [
                'required',
                'integer',
                Rule::exists('folders', 'id')->whereNull('deleted_at'),
            ],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id'),
            ],
            // max is in KILOBYTES → 25600 KB = 25 MB (contract)
            'file'          => [
                'required',
                'file',
                'max:25600',
                'mimes:pdf,jpg,jpeg,png,webp,gif,csv,xlsx,doc,docx,txt,zip',
            ],
        ];
    }
}
