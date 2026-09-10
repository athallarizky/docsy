<?php

namespace App\Services\Folder;

use Illuminate\Support\Facades\DB;

class GetBreadcrumbsService
{
    public function execute(int $folderId): array
    {
        $sql = "
            WITH RECURSIVE breadcrumbs AS (
                -- ANCHOR: mulai dari folder yang diminta
                SELECT id, name, parent_id, 1 AS depth
                FROM folders
                WHERE id = :folder_id AND deleted_at IS NULL

                UNION ALL

                -- RECURSIVE: naik ke induk dari hasil sebelumnya
                SELECT f.id, f.name, f.parent_id, b.depth + 1
                FROM folders f
                INNER JOIN breadcrumbs b ON f.id = b.parent_id
                WHERE f.deleted_at IS NULL
            )
            SELECT id, name, parent_id
            FROM breadcrumbs
            ORDER BY depth DESC;
        ";

        return DB::select($sql, ['folder_id' => $folderId]);
    }
}
