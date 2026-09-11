<?php

namespace App\Observers;

use App\Models\File;
use App\Support\CacheInvalidations;

class FileObserver
{
    /**
     * File mutations only affect dashboard counts (recent files + totals).
     * Folder tree and department list don't depend on files.
     */
    public function created(File $file): void
    {
        CacheInvalidations::fileChanged();
    }

    public function updated(File $file): void
    {
        CacheInvalidations::fileChanged();
    }

    public function deleted(File $file): void
    {
        CacheInvalidations::fileChanged();
    }
}
