<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * GET /api/v1/activity-logs — admin, paginated. NEVER cached:
     * audit data must be fresh at all times.
     */
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'message' => 'Activity logs',
            'data'    => collect($logs->items())->map(fn(ActivityLog $log) => [
                'id'          => $log->id,
                'user'        => $log->user?->name,
                'action'      => $log->action,
                'entity_type' => class_basename($log->entity_type),
                'entity_id'   => $log->entity_id,
                'description' => $log->metadata['title']
                    ?? $log->metadata['original_name']
                    ?? null,
                'created_at'  => $log->created_at?->toISOString(),
            ]),
            'meta'    => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }
}
