<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\File
 */
class FileResource extends JsonResource
{
    /**
     * Contract shape — note what is ABSENT on purpose:
     * storage_path must never leak to the client.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'title'               => $this->title,
            'original_name'       => $this->original_name,
            'mime_type'           => $this->mime_type,
            'file_size'           => $this->file_size,
            'file_size_formatted' => $this->file_size_formatted, // accessor
            'folder'              => $this->whenLoaded('folder', fn() => [
                'id'   => $this->folder->id,
                'name' => $this->folder->name,
            ]),
            'department'          => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
            'uploaded_by'         => $this->whenLoaded('uploader', fn() => [
                'id'   => $this->uploader->id,
                'name' => $this->uploader->name,
            ]),
            'created_at'          => $this->created_at?->toISOString(),
        ];
    }
}
