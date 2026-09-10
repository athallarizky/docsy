<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\FileController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\FolderController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/folders/{folder}/breadcrumbs', [FolderController::class, 'breadcrumbs']);

    Route::apiResource('files', FileController::class)->only(['store']);
    Route::apiResources([
        'departments' => DepartmentController::class,
        'folders'     => FolderController::class,
    ]);
});
