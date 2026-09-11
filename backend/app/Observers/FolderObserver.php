<?php

namespace App\Observers;

use App\Models\Folder;
use App\Support\CacheInvalidations;

class FolderObserver
{
    public function created(Folder $folder): void
    {
        CacheInvalidations::folderChanged();
    }

    public function updated(Folder $folder): void
    {
        CacheInvalidations::folderChanged();
    }

    public function deleted(Folder $folder): void
    {
        CacheInvalidations::folderChanged();
    }
}
