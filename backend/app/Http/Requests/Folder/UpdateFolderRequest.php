<?php

namespace App\Http\Requests\Folder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int|string>>
     */
    public function rules(): array
    {
        $parentId = $this->input('parent_id');
        $folderId = $this->route('folder')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('folders', 'name')
                    ->where(function ($query) use ($parentId) {
                        if ($parentId === null) {
                            $query->whereNull('parent_id');
                        } else {
                            $query->where('parent_id', $parentId);
                        }
                    })
                    ->whereNull('deleted_at')
                    ->ignore($folderId),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('folders', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
