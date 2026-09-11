<?php

namespace App\Services\Dashboard;

use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Support\Facades\Cache;

class GetDashboardStatsService
{
    public const CACHE_KEY = 'docsy:dash:stats';
    private const TTL = 600; // 10 minutes

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, function () {
            return [
                'total_folders'      => Folder::count(),
                'total_files'        => File::count(),
                'total_departments'  => Department::count(),
                'recent_files'       => File::query()
                    ->with(['department', 'uploader'])
                    ->latest('id')
                    ->limit(10)
                    ->get()
                    ->map(fn(File $file) => [
                        'id'                => $file->id,
                        'title'             => $file->title,
                        'original_name'     => $file->original_name,
                        'department_name'   => $file->department?->name,
                        'uploaded_by_name'  => $file->uploader?->name,
                        'created_at'        => $file->created_at?->toISOString(),
                    ])
                    ->all(),
            ];
        });
    }
}
