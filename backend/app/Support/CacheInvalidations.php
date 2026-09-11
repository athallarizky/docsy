<?php

namespace App\Support;

use App\Services\Dashboard\GetDashboardStatsService;
use App\Services\Folder\BuildFolderTreeService;
use Illuminate\Support\Facades\Cache;

/**
 * The single source of truth for "which cache keys die when which model
 * changes". Observers stay thin; this map stays reviewable in one place.
 */
class CacheInvalidations
{
    public static function fileChanged(): void
    {
        Cache::forget(GetDashboardStatsService::CACHE_KEY);
    }

    public static function folderChanged(): void
    {
        Cache::forget(GetDashboardStatsService::CACHE_KEY);
        Cache::forget(BuildFolderTreeService::CACHE_KEY);
    }

    public static function departmentChanged(): void
    {
        Cache::forget(GetDashboardStatsService::CACHE_KEY);
        Cache::forget('docsy:depts:list');
    }
}
