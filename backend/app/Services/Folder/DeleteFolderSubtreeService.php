<?php

namespace App\Services\Folder;

use App\Models\Folder;
use Illuminate\Support\Facades\DB;

class DeleteFolderSubtreeService
{
    public function execute(Folder $folder): void
    {
        $sql = "
            WITH RECURSIVE descendants AS (
                SELECT id
                FROM folders
                WHERE id = :folder_id

                UNION ALL

                SELECT f.id
                FROM folders f
                INNER JOIN descendants d ON f.parent_id = d.id
            )
            UPDATE folders
            SET deleted_at = NOW(), updated_at = NOW()
            WHERE id IN (SELECT id FROM descendants)
              AND deleted_at IS NULL;
        ";

        DB::update($sql, ['folder_id' => $folder->id]);
    }
}
