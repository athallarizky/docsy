<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\FileController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\FolderController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\DashboardController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // admin-only gates (model-less resources)
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
        ->middleware('can:view-dashboard');
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('can:view-activity-logs');

    Route::get('/folders/tree', [FolderController::class, 'tree']);

    Route::get('/folders/{folder}/breadcrumbs', [FolderController::class, 'breadcrumbs']);

    Route::get('/files/{file}/download', [FileController::class, 'download']);
    Route::get('/files/{file}/preview', [FileController::class, 'preview']);

    Route::apiResource('files', FileController::class);
    Route::apiResources([
        'departments' => DepartmentController::class,
        'folders'     => FolderController::class,
    ]);
});
