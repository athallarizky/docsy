<?php

namespace App\Services\Folder;

use App\Models\Folder;
use Illuminate\Support\Facades\Cache;

class BuildFolderTreeService
{
    public const CACHE_KEY = 'docsy:folders:tree';
    private const TTL = 300; // 5 minutes — safety net until event invalidation

    /**
     * Full nested tree, cached. ONE query — nesting is assembled in PHP.
     *
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, function () {
            $folders = Folder::query()->orderBy('name')->get(['id', 'name', 'parent_id']);

            // index by id for O(1) parent lookups
            $nodes = [];
            foreach ($folders as $folder) {
                $nodes[$folder->id] = [
                    'id'        => $folder->id,
                    'name'      => $folder->name,
                    'parent_id' => $folder->parent_id,
                    'children'  => [],
                ];
            }

            // attach each node to its parent — roots stay top-level
            $tree = [];
            foreach ($nodes as $id => &$node) {
                if ($node['parent_id'] !== null && isset($nodes[$node['parent_id']])) {
                    $nodes[$node['parent_id']]['children'][] = &$node;
                } else {
                    $tree[] = &$node;
                }
            }

            return $tree;
        });
    }
}
