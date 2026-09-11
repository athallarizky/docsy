<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\GetDashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/dashboard/stats — admin only (contract §6), cached.
     */
    public function stats(Request $request, GetDashboardStatsService $stats): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats',
            'data'    => $stats->execute(),
        ]);
    }
}
