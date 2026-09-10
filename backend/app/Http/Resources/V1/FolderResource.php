<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Folder
 */
class FolderResource extends JsonResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'parent_id'  => $this->parent_id,
            'created_by' => $this->creator?->name,
            'created_at' => $this->created_at?->toISOString(),
            // 'whenLoaded' = only show when eager-load
            'children'   => FolderResource::collection($this->whenLoaded('children')),
        ];
    }
}
