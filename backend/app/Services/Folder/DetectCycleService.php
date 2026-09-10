<?php

namespace App\Services\Folder;

use Illuminate\Support\Facades\DB;

class DetectCycleService
{
    public function wouldCauseCycle(int $folderId, int $targetParentId): bool
    {
        if ($folderId === $targetParentId) {
            return true;
        }

        $sql = "
            WITH RECURSIVE descendants AS (
                SELECT id, parent_id
                FROM folders
                WHERE id = :folder_id AND deleted_at IS NULL

                UNION ALL

                -- TURUN: anak-anak dari hasil sebelumnya
                SELECT f.id, f.parent_id
                FROM folders f
                INNER JOIN descendants d ON f.parent_id = d.id
                WHERE f.deleted_at IS NULL
            )
            SELECT id FROM descendants WHERE id = :target_parent_id;
        ";

        $result = DB::select($sql, [
            'folder_id'       => $folderId,
            'target_parent_id' => $targetParentId,
        ]);

        return ! empty($result);
    }
}
